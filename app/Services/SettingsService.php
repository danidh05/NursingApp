<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Support\Collection;

class SettingsService
{
    /**
     * Get all settings
     */
    public function getAll(): Collection
    {
        return Settings::orderBy('key')->get();
    }

    /**
     * Get active settings only
     */
    public function getActive(): Collection
    {
        return Settings::where('is_active', true)->orderBy('key')->get();
    }

    /**
     * Get a specific setting by key
     */
    public function getByKey(string $key): ?Settings
    {
        return Settings::where('key', $key)->first();
    }

    /**
     * Get setting value by key
     */
    public function getValue(string $key, $default = null)
    {
        return Settings::getValue($key, $default);
    }

    /**
     * Set a setting value
     */
    public function setValue(string $key, $value, string $type = 'string', string $description = null): Settings
    {
        return Settings::setValue($key, $value, $type, $description);
    }

    /**
     * Update a setting
     */
    public function update(int $id, array $data): Settings
    {
        $setting = Settings::findOrFail($id);
        $setting->update($data);
        return $setting->fresh();
    }

    /**
     * Create a new setting
     */
    public function create(array $data): Settings
    {
        return Settings::create($data);
    }

    /**
     * Delete a setting
     */
    public function delete(int $id): bool
    {
        $setting = Settings::findOrFail($id);
        return $setting->delete();
    }

    /**
     * Toggle setting active status
     */
    public function toggleActive(int $id): Settings
    {
        $setting = Settings::findOrFail($id);
        $setting->update(['is_active' => !$setting->is_active]);
        return $setting->fresh();
    }

    /**
     * Get job application URL
     */
    public function getJobApplicationUrl(): ?string
    {
        return $this->getValue('job_application_url');
    }

    /**
     * Set job application URL
     */
    public function setJobApplicationUrl(string $url): Settings
    {
        return $this->setValue(
            'job_application_url',
            $url,
            'url',
            'URL for job application redirect'
        );
    }

    /**
     * Get WhatsApp support number
     */
    public function getWhatsAppSupportNumber(): ?string
    {
        return $this->getValue('whatsapp_support_number');
    }

    /**
     * Set WhatsApp support number
     */
    public function setWhatsAppSupportNumber(string $number): Settings
    {
        return $this->setValue(
            'whatsapp_support_number',
            $number,
            'phone',
            'WhatsApp number for customer support'
        );
    }

    /**
     * Get all public settings (for frontend)
     */
    public function getPublicSettings(): array
    {
        return [
            'job_application_url' => $this->getJobApplicationUrl(),
            'whatsapp_support_number' => $this->getWhatsAppSupportNumber(),
        ];
    }
}
