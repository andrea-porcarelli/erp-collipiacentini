<?php

namespace App\Http\Controllers\Backoffice;

use App\Interfaces\PartnerConsentInterface;
use App\Interfaces\PartnerInterface;
use App\Models\Language;
use App\Models\PartnerConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerConsentController extends CrudController
{
    public PartnerConsentInterface $interface;

    public PartnerInterface $partnerInterface;

    public string $path = 'partner-consents';

    public function __construct(PartnerConsentInterface $interface, PartnerInterface $partnerInterface)
    {
        $this->interface = $interface;
        $this->partnerInterface = $partnerInterface;
    }

    public function index(int $partnerId): JsonResponse
    {
        try {
            $items = PartnerConsent::where('partner_id', $partnerId)
                ->current()
                ->orderBy('position')
                ->get()
                ->map(fn ($c) => $this->serialize($c));

            return $this->success(['data' => $items]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function enable(int $partnerId): JsonResponse
    {
        try {
            $partner = $this->partnerInterface->find($partnerId);
            $this->partnerInterface->edit($partner, ['consents_enabled' => true]);

            $consent = PartnerConsent::firstOrCreate(
                ['partner_id' => $partner->id, 'code' => PartnerConsent::CODE_TERMS],
                [
                    'is_required' => true,
                    'is_locked' => true,
                    'expiry_days' => 0,
                    'expiry_months' => 0,
                    'expiry_years' => 10,
                    'position' => 0,
                ]
            );

            if (! $consent->contentField('content', 'it')) {
                $consent->setContentFields([
                    'content' => 'Accetto le <a href="###">condizioni e termini</a> e ho preso visione <a href="###">dell\'informativa Privacy</a>',
                ], 'it');
            }

            return $this->success(['consent' => $this->serialize($consent->fresh())]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function store(Request $request, int $partnerId): JsonResponse
    {
        try {
            $partner = $this->partnerInterface->find($partnerId);

            $data = $this->validatedPayload($request);
            $position = (int) (PartnerConsent::where('partner_id', $partner->id)->max('position') ?? -1) + 1;

            $consent = $this->interface->store([
                'partner_id' => $partner->id,
                'is_required' => $data['is_required'],
                'is_locked' => false,
                'expiry_days' => $data['expiry_days'],
                'expiry_months' => $data['expiry_months'],
                'expiry_years' => $data['expiry_years'],
                'position' => $position,
            ]);

            $this->applyTranslations($consent, $data['content_translations']);

            return $this->success(['consent' => $this->serialize($consent->fresh())]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function update(Request $request, int $partnerId, int $consentId): JsonResponse
    {
        try {
            $consent = PartnerConsent::where('partner_id', $partnerId)
                ->current()
                ->findOrFail($consentId);
            $data = $this->validatedPayload($request);

            $update = [];
            if (! $consent->is_locked) {
                $update['is_required'] = $data['is_required'];
                $update['expiry_days'] = $data['expiry_days'];
                $update['expiry_months'] = $data['expiry_months'];
                $update['expiry_years'] = $data['expiry_years'];
            }
            if (! empty($update)) {
                $this->interface->edit($consent, $update);
            }

            $textChanged = $this->contentChanged($consent, $data['content_translations']);

            if ($textChanged && $consent->orderConsents()->exists()) {
                // Copy-on-write: creiamo una nuova versione, quella vecchia resta
                // puntata dagli ordini storici come snapshot immutabile.
                $newConsent = \Illuminate\Support\Facades\DB::transaction(function () use ($consent, $data) {
                    $new = PartnerConsent::create([
                        'partner_id' => $consent->partner_id,
                        'code' => $consent->code,
                        'version' => (int) $consent->version + 1,
                        'is_required' => $consent->is_required,
                        'is_locked' => $consent->is_locked,
                        'is_active' => $consent->is_active,
                        'expiry_days' => $consent->expiry_days,
                        'expiry_months' => $consent->expiry_months,
                        'expiry_years' => $consent->expiry_years,
                        'position' => $consent->position,
                    ]);

                    $this->applyTranslations($new, $data['content_translations']);

                    $consent->update([
                        'superseded_at' => now(),
                        'superseded_by_id' => $new->id,
                    ]);

                    return $new;
                });

                return $this->success(['consent' => $this->serialize($newConsent->fresh())]);
            }

            $this->applyTranslations($consent, $data['content_translations']);

            return $this->success(['consent' => $this->serialize($consent->fresh())]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    /**
     * True se almeno una traduzione contenuto differisce da quella salvata attuale.
     */
    private function contentChanged(PartnerConsent $consent, array $translations): bool
    {
        foreach ($translations as $isoCode => $html) {
            if (! is_string($isoCode) || $isoCode === '') {
                continue;
            }
            $current = (string) ($consent->contentField('content', $isoCode) ?? '');
            if ((string) $html !== $current) {
                return true;
            }
        }

        return false;
    }

    public function toggleActive(Request $request, int $partnerId, int $consentId): JsonResponse
    {
        try {
            $consent = PartnerConsent::where('partner_id', $partnerId)->findOrFail($consentId);
            $this->interface->edit($consent, ['is_active' => (bool) $request->input('is_active')]);

            return $this->success(['consent' => $this->serialize($consent->fresh())]);
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function reorder(Request $request, int $partnerId): JsonResponse
    {
        try {
            $ids = (array) $request->input('ordered_ids', []);
            foreach ($ids as $position => $id) {
                PartnerConsent::where('partner_id', $partnerId)
                    ->where('id', (int) $id)
                    ->update(['position' => (int) $position]);
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    public function destroy(int $partnerId, int $consentId): JsonResponse
    {
        try {
            $consent = PartnerConsent::where('partner_id', $partnerId)->findOrFail($consentId);
            if ($consent->is_locked) {
                return $this->exception(new \Exception('Questo consenso non può essere eliminato.'));
            }
            if ($consent->orderConsents()->exists()) {
                // Soft-delete via superseded_at: gli ordini storici continuano
                // a puntare a questo record e ne conservano il testo integrale.
                $consent->update(['superseded_at' => now()]);

                return $this->success();
            }
            $consent->delete();

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function getTranslations(int $partnerId, int $consentId): JsonResponse
    {
        try {
            $consent = PartnerConsent::where('partner_id', $partnerId)->findOrFail($consentId);
            $languages = Language::where('is_active', 1)->get();

            $data = $languages->map(fn ($lang) => [
                'language_id' => $lang->id,
                'language' => $lang->label,
                'iso_code' => $lang->iso_code,
                'content' => $consent->contentField('content', $lang->iso_code) ?? '',
            ]);

            return $this->success(['data' => $data]);
        } catch (\Exception $e) {
            return $this->exception($e);
        }
    }

    public function saveTranslations(Request $request, int $partnerId, int $consentId): JsonResponse
    {
        try {
            $consent = PartnerConsent::where('partner_id', $partnerId)->findOrFail($consentId);

            foreach ($request->input('translations', []) as $t) {
                $lang = Language::find($t['language_id'] ?? null);
                if (! $lang) {
                    continue;
                }
                $consent->setContentFields(['content' => $t['content'] ?? ''], $lang->iso_code);
            }

            return $this->success();
        } catch (\Exception $e) {
            return $this->exception($e, $request);
        }
    }

    private function validatedPayload(Request $request): array
    {
        $request->validate([
            'is_required' => 'required|boolean',
            'expiry_days' => 'required|integer|min:0',
            'expiry_months' => 'required|integer|min:0',
            'expiry_years' => 'required|integer|min:0',
            'content_translations' => 'nullable|array',
        ]);

        return [
            'is_required' => (bool) $request->input('is_required'),
            'expiry_days' => (int) $request->input('expiry_days'),
            'expiry_months' => (int) $request->input('expiry_months'),
            'expiry_years' => (int) $request->input('expiry_years'),
            'content_translations' => (array) $request->input('content_translations', []),
        ];
    }

    private function applyTranslations(PartnerConsent $consent, array $translations): void
    {
        if (empty($translations)) {
            return;
        }
        foreach ($translations as $isoCode => $html) {
            if (! is_string($isoCode) || $isoCode === '') {
                continue;
            }
            $consent->setContentFields(['content' => (string) $html], $isoCode);
        }
    }

    private function serialize(PartnerConsent $c): array
    {
        return [
            'id' => $c->id,
            'code' => $c->code,
            'version' => (int) $c->version,
            'is_required' => (bool) $c->is_required,
            'is_locked' => (bool) $c->is_locked,
            'is_active' => (bool) $c->is_active,
            'has_customers' => $c->orderConsents()->exists(),
            'expiry_days' => (int) $c->expiry_days,
            'expiry_months' => (int) $c->expiry_months,
            'expiry_years' => (int) $c->expiry_years,
            'position' => (int) $c->position,
            'content_it' => $c->contentField('content', 'it') ?? '',
        ];
    }
}
