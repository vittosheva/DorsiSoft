<?php

declare(strict_types=1);

use Modules\Core\Support\CustomerEmailNormalizer;

describe('SendElectronicDocumentEmailService', function () {
    it('normalizes email arrays correctly via CustomerEmailNormalizer', function () {
        $normalizer = new CustomerEmailNormalizer();
        $result = $normalizer->normalizeAsArray(['email1@test.com', 'email2@test.com']);

        expect($result)->toBeArray()
            ->toHaveCount(2)
            ->toContain('email1@test.com')
            ->toContain('email2@test.com');
    });

    it('normalizes string emails via CustomerEmailNormalizer', function () {
        $normalizer = new CustomerEmailNormalizer();
        $result = $normalizer->normalizeAsArray('test@example.com');

        expect($result)->toBeArray()
            ->toHaveCount(1);

        expect($result[0])->toBe('test@example.com');
    });

    it('handles null emails via CustomerEmailNormalizer', function () {
        $normalizer = new CustomerEmailNormalizer();
        $result = $normalizer->normalizeAsArray(null);

        expect($result)->toBeNull();
    });

    it('handles empty arrays via CustomerEmailNormalizer', function () {
        $normalizer = new CustomerEmailNormalizer();
        $result = $normalizer->normalizeAsArray([]);

        expect($result)->toBeNull();
    });
});
