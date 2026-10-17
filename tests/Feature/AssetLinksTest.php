<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssetLinksTest extends TestCase
{
    public function test_assetlinks_json_advertises_the_android_package(): void
    {
        $response = $this->get(route('assetlinks'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/json',
            (string) $response->headers->get('content-type')
        );

        $payload = $response->json();
        $this->assertSame('com.asloobulhayat.eternalecho', $payload[0]['target']['package_name']);
        $this->assertNotEmpty($payload[0]['target']['sha256_cert_fingerprints']);
        $this->assertContains(
            'delegate_permission/common.handle_all_urls',
            $payload[0]['relation']
        );
    }
}
