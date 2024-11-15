<?php

namespace Tests\Feature;

use App\Enums\GroupStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\Assert;
use Tests\TestCase;

class GroupsJsonTest extends TestCase
{
	public function test_json_is_valid(): void
	{
		$json = json_decode(file_get_contents(base_path('groups.json')), true);
		
		foreach ($json as $domain => $config) {
			$external = data_get($config, 'external', false);
			$assertion = $external ? $this->assertValidExternalGroup(...) : $this->assertValidGroup(...);
			$assertion($domain, $config);
		}
	}
	
	protected function assertValidGroup(string $domain, array $config): void
	{
		$this->assertValidDomain($domain);
		
		Assert::assertNotEmpty(data_get($config, 'name'));
		Assert::assertNotEmpty(data_get($config, 'description'));
		Assert::assertContains(data_get($config, 'timezone'), \DateTimeZone::listIdentifiers());
		
		if ($status = data_get($config, 'status')) {
			Assert::assertNotNull(GroupStatus::from($status));
		}
		
		if ($frequency = data_get($config, 'frequency')) {
			Assert::assertTrue(Str::contains($frequency, ['week', 'month', 'quarter', 'year']));
		}
		
		if ($bsky_url = data_get($config, 'bsky_url')) {
			Assert::assertEquals(200, Http::get($bsky_url)->status());
		}
	}
	
	protected function assertValidExternalGroup(string $domain, array $config): void
	{
		$this->assertValidDomain($domain);
		
		Assert::assertNotEmpty(data_get($config, 'name'));
	}
	
	protected function assertValidDomain($value): void
	{
		Assert::assertIsString($value, "Domain must be a string.");
		Assert::assertTrue(false !== filter_var($value, FILTER_VALIDATE_DOMAIN), "Domain format is invalid.");
		
		$records = dns_get_record("{$value}.", DNS_A | DNS_AAAA);
		
		Assert::assertIsArray($records, "There aren't DNS records for $value");
		Assert::assertNotEmpty($records, "There aren't DNS records for $value");
	}
}