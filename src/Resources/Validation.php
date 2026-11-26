<?php

declare(strict_types=1);

namespace GetApi\Resources;

use GetApi\Response;
use InvalidArgumentException;

/**
 * Controles: BTW-nummer (VIES), IBAN, e-mailadres en IP-blacklists.
 *
 * Een ongeldig BTW-nummer, IBAN of e-mailadres is geen fout. Je krijgt dan een antwoord met `valid: false`.
 * Controleer dus `$response->data['valid']`. Een echte invoerfout (bijvoorbeeld een te lang e-mailadres of een
 * ontbrekend veld) geeft wel een ValidationException.
 */
class Validation extends AbstractResource
{
    private const MAX_EMAIL_BATCH = 25;

    /**
     * Controleer een Europees BTW-nummer via VIES.
     *
     * Geldige nummers worden 24 uur bewaard, ongeldige 1 uur. Is VIES tijdelijk niet bereikbaar, dan geeft dit
     * een UpstreamUnavailableException.
     *
     * @param string $vatNumber BTW-nummer, beginnend met de landcode (bijvoorbeeld `NL820646660B01`).
     */
    public function vat(string $vatNumber): Response
    {
        return $this->transport->post('/v1/validate/vat', ['vat_number' => preg_replace('/\s+/', '', $this->requireNonEmpty($vatNumber, 'Het BTW-nummer')) ?? ''], true);
    }

    /**
     * Controleer een IBAN en zoek de bank en BIC op. De controle gebeurt offline voor meer dan 70 landen.
     *
     * @param string      $iban IBAN, met of zonder spaties.
     * @param string|null $bic  BIC om tegen de IBAN te controleren (optioneel).
     */
    public function iban(string $iban, ?string $bic = null): Response
    {
        $body = ['iban' => $this->requireNonEmpty($iban, 'De IBAN')];
        if ($bic !== null && $bic !== '') {
            $body['bic'] = $bic;
        }

        return $this->transport->post('/v1/validate/iban', $body, true);
    }

    /**
     * Controleer de opmaak, het domein en de mailserver van een e-mailadres.
     *
     * @param string $email Het e-mailadres (maximaal 255 tekens).
     */
    public function email(string $email): Response
    {
        return $this->transport->post('/v1/validate/email', ['email' => $this->requireNonEmpty($email, 'Het e-mailadres')], true);
    }

    /**
     * Controleer maximaal 25 e-mailadressen in één aanroep.
     *
     * @param array<string> $emails Tussen 1 en 25 e-mailadressen.
     *
     * @throws InvalidArgumentException Als de lijst leeg is of meer dan 25 adressen bevat.
     */
    public function emailBatch(array $emails): Response
    {
        if ($emails === [] || count($emails) > self::MAX_EMAIL_BATCH) {
            throw new InvalidArgumentException('Geef tussen 1 en 25 e-mailadressen op.');
        }

        return $this->transport->post('/v1/validate/email/batch', ['emails' => array_values($emails)], true);
    }

    /**
     * Controleer of een e-mailadres bij een wegwerpdomein hoort.
     *
     * @param string $email Het e-mailadres (maximaal 255 tekens).
     */
    public function disposableEmail(string $email): Response
    {
        return $this->transport->post('/v1/validate/email/disposable', ['email' => $this->requireNonEmpty($email, 'Het e-mailadres')], true);
    }

    /**
     * Controleer of een IP-adres op een DNS-blacklist (RBL) staat.
     *
     * @param string $ip Het IP-adres.
     */
    public function dnsbl(string $ip): Response
    {
        return $this->transport->post('/v1/validate/dnsbl', ['ip' => $this->requireNonEmpty($ip, 'Het IP-adres')]);
    }
}
