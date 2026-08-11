<?php

namespace Dct\HookNotification\Core\Notification\Domain;

use Dct\HookNotification\Core\Shared\Infrastructure\Config\Settings;
use Dct\HookNotification\Core\Shared\Infrastructure\Repository\ClientRepository;
use Dct\HookNotification\Core\Shared\Validators\PhoneNumberValidator;
use Throwable;
use WHMCS\Database\Capsule;

final class Client
{
    // FIX: Changed properties to accept strings
    public ?string $wpPhoneNumber = null;
    public readonly ?string $whmcsPhoneNumber;
    private readonly ClientRepository $clientRepository;
    public readonly string $locale;

    /**
     * Is null when the client is not registered.
     *
     * @var string|null
     */
    public readonly ?string $countryCode;

    public function __construct(
        public readonly int $id,
    ) {
        $this->clientRepository = new ClientRepository();

        $whmcsPhoneNumber       = $this->clientRepository->getWhmcsPhoneNumber($this->id);
        // FIX: Removed (int) cast. Kept as string after regex.
        $this->whmcsPhoneNumber = $whmcsPhoneNumber ? preg_replace('/[^0-9+]/', '', $whmcsPhoneNumber) : null;
        
        $countryCode            = $this->clientRepository->getClientCountry($this->id);

        $this->countryCode = $countryCode;
        $this->locale      = $this->clientRepository->getClientLang($this->id)['locale'];
    }

    // FIX: Return type is now false|string
    public function validateWpPhoneNumber(int $customFieldId): false|string
    {
        // FIX: Removed (int) cast when fetching the field. Keep it as string.
        $wpPhoneNumberRaw = $this->clientRepository->getCustomField($this->id, $customFieldId);

        // FIX: Removed (int) cast.
        $wpPhoneNumber = $wpPhoneNumberRaw ? preg_replace('/[^0-9+]/', '', strval($wpPhoneNumberRaw)) : null;

        if (!$wpPhoneNumber) {
            return false;
        }

        $wpPhoneNumber = $this->normalizeForCountry($wpPhoneNumber);

        if (!$wpPhoneNumber || !$this->validatePhoneNumber($wpPhoneNumber)) {
            return false;
        }

        $this->wpPhoneNumber = $wpPhoneNumber;
        return $this->wpPhoneNumber;
    }

    // FIX: Return type is now false|string
    public function validateWhmcsPhoneNumber(): false|string
    {
        if (empty($this->whmcsPhoneNumber)) {
            return false;
        }

        $normalized = $this->normalizeForCountry($this->whmcsPhoneNumber);

        if (!$normalized || !$this->validatePhoneNumber($normalized)) {
            return false;
        }

        return $normalized;
    }

    /**
     * Adds the client's country dial code to a phone number that's missing
     * it (e.g. "9846260002" -> "919846260002" for an India-based client),
     * so a number entered in local format isn't rejected just for lacking
     * a prefix most people don't think to add when filling in their own
     * phone number. Numbers that already start with a country code (their
     * own or another one, for a client who travels/relocated) are left
     * untouched.
     *
     * @since 4.6.2
     */
    private function normalizeForCountry(string $phoneNumber): ?string
    {
        if (empty($this->countryCode)) {
            return $phoneNumber;
        }

        return PhoneNumberValidator::getInstance()->normalize($phoneNumber, $this->countryCode) ?: $phoneNumber;
    }

    // FIX: Return type is now false|string
    public function getWpPhoneNumberOrWhmcsPhoneNumber(?int $platformSpecificWpCustomFieldId): false|string
    {
        /** @var null|int $globalWpCustomFieldId */
        $globalWpCustomFieldId = lkn_hn_config(Settings::WP_CUSTOM_FIELD_ID);

        if ($platformSpecificWpCustomFieldId && !$globalWpCustomFieldId) {
            return $this->validateWpPhoneNumber($platformSpecificWpCustomFieldId);
        }

        if ($globalWpCustomFieldId) {
            return $this->validateWpPhoneNumber($globalWpCustomFieldId);
        }

        return $this->validateWhmcsPhoneNumber();
    }

    /**
     * Valides the phone number against the client country.
     *
     * @param  string $phoneNumber // FIX: Accepts string instead of int
     *
     * @return boolean
     */
    private function validatePhoneNumber(string $phoneNumber): bool
    {
        try {
            if (empty($phoneNumber)) {
                return false;
            }

            if (!PhoneNumberValidator::getInstance()->isValid($phoneNumber, $this->countryCode)) {
                return false;
            }

            return true;
        } catch (Throwable $th) {
            lkn_hn_log(
                'Validate client phone number',
                ['phoneNumber' => $phoneNumber],
                ['exception' => $th->__toString()]
            );

            return false;
        }
    }

    /**
     * @param  integer|null $clientId
     * @param  integer      $customFieldId
     *
     * @return string
     */
    public function getCustomField(?int $clientId, int $customFieldId): string
    {
        $query = Capsule::table('tblcustomfieldsvalues');

        if (!is_null($clientId) && $clientId !== 0) {
            $query = $query->where('relid', $clientId);
        }

        $customFieldValue = $query = $query->where('fieldid', $customFieldId)
            ->first('value')
            ->value;

        return $customFieldValue;
    }
}
