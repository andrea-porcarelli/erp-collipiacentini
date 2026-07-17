-- Backfill dei timestamp da UTC a Europe/Rome
-- ================================================================
-- Da eseguire UNA SOLA VOLTA in produzione, subito dopo il deploy di
-- `config/app.php` -> 'timezone' => env('APP_TIMEZONE', 'UTC')
-- e prima che nuovi record vengano scritti (mettere l'app in
-- maintenance mode: `php artisan down` durante l'esecuzione).
--
-- Prerequisito: le tabelle timezone di MySQL devono essere popolate.
-- Verifica con:
--   SELECT CONVERT_TZ(NOW(), '+00:00', 'Europe/Rome');
-- Se restituisce NULL, esegui prima (come root sull'host DB):
--   mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p mysql
--
-- CONVERT_TZ gestisce automaticamente il DST (+1h inverno, +2h estate).
-- I NULL restano NULL, quindi è safe applicare a colonne nullable.
-- ================================================================

START TRANSACTION;

-- ----- created_at / updated_at su tutte le tabelle applicative -----

UPDATE activity_log             SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE cart_items               SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE carts                    SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE categories               SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE companies                SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE company_product          SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE countries                SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE customer_consents        SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE customer_field_types     SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE customers                SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE language_contents        SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE languages                SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE media                    SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE order_logs               SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE order_participants       SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE order_product_items      SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE order_products           SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE orders                   SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE partner_billings         SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE partner_consents         SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE partners                 SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_availabilities   SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_closed_periods   SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_customer_fields  SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_faqs             SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_features         SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_links            SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_price_variations SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_product_feature  SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_related          SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_special_schedules SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_variant_prices   SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE product_variants         SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE products                 SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');
UPDATE users                    SET created_at = CONVERT_TZ(created_at, '+00:00', 'Europe/Rome'), updated_at = CONVERT_TZ(updated_at, '+00:00', 'Europe/Rome');

-- ----- Colonne datetime custom -----

UPDATE users             SET email_verified_at = CONVERT_TZ(email_verified_at, '+00:00', 'Europe/Rome') WHERE email_verified_at IS NOT NULL;
UPDATE orders            SET paid_at           = CONVERT_TZ(paid_at, '+00:00', 'Europe/Rome')           WHERE paid_at IS NOT NULL;
UPDATE customer_consents SET subscribed_at     = CONVERT_TZ(subscribed_at, '+00:00', 'Europe/Rome')     WHERE subscribed_at IS NOT NULL;
UPDATE customer_consents SET expires_at        = CONVERT_TZ(expires_at, '+00:00', 'Europe/Rome')        WHERE expires_at IS NOT NULL;

-- Verifica prima di committare (opzionale):
--   SELECT id, created_at FROM orders ORDER BY id DESC LIMIT 5;

COMMIT;
-- In caso di problemi: ROLLBACK;
