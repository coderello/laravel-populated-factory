<?php

namespace Coderello\PopulatedFactory;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Str;

class FakeValueExpressionGuesser
{
    public function guess(Column $column)
    {
        if ($this->guessable($column->getType()->getName())) {
            return $this->{$this->methodName($column->getType()->getName())}($column);
        }

        return null;
    }

    public function missingTypeNames(): array
    {
        return collect(Type::getTypesMap())
            ->keys()
            ->reject(function ($name) {
                return $this->guessable($name);
            })
            ->values()
            ->toArray();
    }

    protected function methodName(string $typeName): string
    {
        return 'guessFor'.Str::title($typeName);
    }

    protected function guessable(string $typeName): bool
    {
        return method_exists($this, $this->methodName($typeName));
    }

    protected function similar(string $columnName, array $comparedNames): bool
    {
        $columnName = str_replace('-', '', Str::slug($columnName));

        foreach ($comparedNames as $comparedName) {
            $levenshtein = levenshtein($comparedName, $columnName);

            if ((Str::length($columnName) >= 4 && $levenshtein <= 1) || $levenshtein === 0) {
                return true;
            }
        }

        return false;
    }

    protected function guessForString(Column $column)
    {
        $name = $column->getName();

        switch (true)
        {
            case $this->similar($name, ['password']):
                return '\'$2y$10$uTDnsRa0h7wLppc8/vB9C.YqsrAZwhjCgLWjcmpbndTmyo1k5tbRC\'';
            case $this->similar($name, ['email', 'emailaddress']):
                return '$this->faker->unique()->safeEmail';
            case $this->similar($name, ['name']):
                return '$this->faker->name';
            case $this->similar($name, ['firstname']):
                return '$this->faker->firstName';
            case $this->similar($name, ['lastname']):
                return '$this->faker->lastName';
            case $this->similar($name, ['address', 'streetaddress']):
                return '$this->faker->streetAddress';
            case $this->similar($name, ['city']):
                return '$this->faker->city';
            case $this->similar($name, ['postcode', 'postalcode']):
                return '$this->faker->postcode';
            case $this->similar($name, ['country']):
                return '$this->faker->country';
            case $this->similar($name, ['phone', 'number', 'phonenumber']):
                return '$this->faker->phoneNumber';
            case $this->similar($name, ['company', 'company_name']):
                return '$this->faker->company';
            case $this->similar($name, ['job', 'jobtitle']):
                return '$this->faker->jobTitle';
            case $this->similar($name, ['credit_card', 'creditcardnumber']):
                return '$this->faker->creditCardNumber';
            case $this->similar($name, ['creditcardexpirationdate', 'expirationdate']):
                return '$this->faker->creditCardExpirationDateString';
            case $this->similar($name, ['username', 'nickname']):
                return '$this->faker->userName';
            case $this->similar($name, ['domain', 'domainname']):
                return '$this->faker->domainName';
            case $this->similar($name, ['tld']):
                return '$this->faker->tld';
            case $this->similar($name, ['url', 'link', 'uri', 'externallink', 'externalurl']):
                return '$this->faker->url';
            case $this->similar($name, ['slug']):
                return '$this->faker->slug';
            case $this->similar($name, ['ip']):
                return '$this->faker->ipv4';
            case $this->similar($name, ['mac', 'macaddress']):
                return '$this->faker->macAddress';
            case $this->similar($name, ['timezone']):
                return '$this->faker->timezone';
            case $this->similar($name, ['countrycode']):
                return '$this->faker->countryCode';
            case $this->similar($name, ['languagecode', 'language', 'locale']):
                return '$this->faker->languageCode';
            case $this->similar($name, ['currencycode', 'currency']):
                return '$this->faker->currencyCode';
            case $this->similar($name, ['useragent']):
                return '$this->faker->userAgent';
            case $this->similar($name, ['uuid']):
                return '$this->faker->uuid';
            case $this->similar($name, ['mime', 'mimetype']):
                return '$this->faker->mimeType';
            case $this->similar($name, ['image', 'imagepath', 'img']):
                return '$this->faker->image';
            case $this->similar($name, ['html']):
                return '$this->faker->randomHtml';
            case $this->similar($name, ['hex', 'color']):
                return '$this->faker->hexColor';
            case Str::contains($name, 'token'):
                return '$this->faker->sha1';
            default:
                return '$this->faker->text('.((int) (($column->getLength() ?? 200) / 4)).')';
        }
    }

    protected function guessForText(Column $column)
    {
        return $this->guessForString($column);
    }

    protected function guessForBoolean(Column $column)
    {
        return '$this->faker->boolean(50)';
    }

    protected function guessForDatetime(Column $column)
    {
        $name = $column->getName();

        switch (true)
        {
            case $this->similar($name, ['expiration_date']):
                return '$this->faker->dateTimeBetween(\'+1 year\', \'+5 years\')';
            case $this->similar($name, ['birth', 'born_at', 'birthday', 'date_of_birth']):
                return '$this->faker->dateTimeBetween(\'-60 years\', \'-1 year\')';
            default:
                return '$this->faker->dateTime';
        }
    }

    protected function guessForDate(Column $column)
    {
        return $this->guessForDatetime($column);
    }

    protected function guessForFloat(Column $column)
    {
        $name = $column->getName();

        switch (true)
        {
            case $this->similar($name, ['lat', 'latitude']):
                return '$this->faker->latitude';
            case $this->similar($name, ['lon', 'lng', 'longitude']):
                return '$this->faker->longitude';
            default:
                return '$this->faker->randomFloat';
        }
    }

    protected function guessForDecimal(Column $column)
    {
        return $this->guessForFloat($column);
    }

    protected function guessForSmallint(Column $column)
    {
        if ($column->getUnsigned()) {
            return '$this->faker->numberBetween(0, 65535)';
        }

        return '$this->faker->numberBetween(-32768, 32767)';
    }

    protected function guessForInteger(Column $column)
    {
        if ($column->getUnsigned()) {
            return '$this->faker->numberBetween(0, 4294967295)';
        }

        return '$this->faker->numberBetween(-2147483648, 2147483647)';
    }

    protected function guessForBigint(Column $column)
    {
        return $this->guessForInteger($column);
    }
}
