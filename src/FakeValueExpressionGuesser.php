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
                return '$faker->md5';
            case $this->similar($name, ['email', 'emailaddress']):
                return '$faker->unique()->safeEmail';
            case $this->similar($name, ['name']):
                return '$faker->name';
            case $this->similar($name, ['firstname']):
                return '$faker->firstName';
            case $this->similar($name, ['lastname']):
                return '$faker->lastName';
            case $this->similar($name, ['address', 'streetaddress']):
                return '$faker->streetAddress';
            case $this->similar($name, ['city']):
                return '$faker->city';
            case $this->similar($name, ['postcode', 'postalcode']):
                return '$faker->postcode';
            case $this->similar($name, ['country']):
                return '$faker->country';
            case $this->similar($name, ['phone', 'number', 'phonenumber']):
                return '$faker->phoneNumber';
            case $this->similar($name, ['company', 'company_name']):
                return '$faker->companyName';
            case $this->similar($name, ['job', 'jobtitle']):
                return '$faker->jobTitle';
            case $this->similar($name, ['credit_card', 'creditcardnumber']):
                return '$faker->creditCardNumber';
            case $this->similar($name, ['creditcardexpirationdate', 'expirationdate']):
                return '$faker->creditCardExpirationDateString';
            case $this->similar($name, ['username', 'nickname']):
                return '$faker->userName';
            case $this->similar($name, ['domain', 'domainname']):
                return '$faker->domainName';
            case $this->similar($name, ['tld']):
                return '$faker->tld';
            case $this->similar($name, ['url', 'link', 'uri', 'externallink', 'externalurl']):
                return '$faker->url';
            case $this->similar($name, ['slug']):
                return '$faker->slug';
            case $this->similar($name, ['ip']):
                return '$faker->ipv4';
            case $this->similar($name, ['mac', 'macaddress']):
                return '$faker->macAddress';
            case $this->similar($name, ['timezone']):
                return '$faker->timezone';
            case $this->similar($name, ['countrycode']):
                return '$faker->countryCode';
            case $this->similar($name, ['languagecode', 'language', 'locale']):
                return '$faker->languageCode';
            case $this->similar($name, ['currencycode', 'currency']):
                return '$faker->currencyCode';
            case $this->similar($name, ['useragent']):
                return '$faker->userAgent';
            case $this->similar($name, ['uuid']):
                return '$faker->uuid';
            case $this->similar($name, ['mime', 'mimetype']):
                return '$faker->mimeType';
            case $this->similar($name, ['image', 'imagepath', 'img']):
                return '$faker->image';
            case $this->similar($name, ['html']):
                return '$faker->randomHtml';
            case $this->similar($name, ['hex', 'color']):
                return '$faker->hexColor';
            case Str::contains($name, 'token'):
                return '$faker->sha1';
            default:
                return '$faker->text('.((int) (($column->getLength() ?? 200) / 4)).')';
        }
    }

    protected function guessForText(Column $column)
    {
        return $this->guessForString($column);
    }

    protected function guessForBoolean(Column $column)
    {
        return '$faker->boolean(50)';
    }

    protected function guessForDatetime(Column $column)
    {
        $name = $column->getName();

        switch (true)
        {
            case $this->similar($name, ['expiration_date']):
                return '$faker->dateTimeBetween(\'+1 year\', \'+5 years\')';
            case $this->similar($name, ['birth', 'born_at', 'birthday', 'date_of_birth']):
                return '$faker->dateTimeBetween(\'-60 years\', \'-1 year\')';
            default:
                return '$faker->dateTime';
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
                return '$faker->latitude';
            case $this->similar($name, ['lon', 'lng', 'longitude']):
                return '$faker->longitude';
            default:
                return '$faker->randomFloat';
        }
    }

    protected function guessForDecimal(Column $column)
    {
        return $this->guessForFloat($column);
    }

    protected function guessForSmallint(Column $column)
    {
        if ($column->getUnsigned()) {
            return '$faker->numberBetween(0, 65535)';
        }

        return '$faker->numberBetween(-32768, 32767)';
    }

    protected function guessForInteger(Column $column)
    {
        if ($column->getUnsigned()) {
            return '$faker->numberBetween(0, 4294967295)';
        }

        return '$faker->numberBetween(-2147483648, 2147483647)';
    }

    protected function guessForBigint(Column $column)
    {
        return $this->guessForInteger($column);
    }
}
