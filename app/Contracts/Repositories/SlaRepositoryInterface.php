<?php

namespace App\Contracts\Repositories;

interface SlaRepositoryInterface
{
    /**
     * @param  string[] $slugs
     * @return \Illuminate\Support\Collection<string, string>  (slug => value)
     */
    public function getSettingsBySlugs(array $slugs): \Illuminate\Support\Collection;

    /**
     * @param array<string, mixed> $data  slug => value
     */
    public function updateSettings(array $data): void;
}
