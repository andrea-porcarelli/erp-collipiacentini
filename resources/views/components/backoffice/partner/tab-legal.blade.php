@props(['model'])

<div class="tab-pane fade" id="partner-legal-panel" role="tabpanel">
    <x-backoffice.partner.tab-policy :model="$model" type="privacy-policy"     field="privacy_policy"   label="Privacy Policy"        slug="privacy-policy"     inline />
    <x-backoffice.partner.tab-policy :model="$model" type="cookie-policy"      field="cookie_policy"    label="Cookie Policy"         slug="cookie-policy"      inline cardClass="mt-4" />
    <x-backoffice.partner.tab-policy :model="$model" type="termini-condizioni" field="terms_conditions" label="Termini e Condizioni"  slug="termini-condizioni" inline cardClass="mt-4" />
</div>
