<?php

namespace Tests\Unit;

use App\DTOs\Contact\ContactResponseDTO;
use App\Models\Contact;
use App\Repositories\Interfaces\IContactRepository;
use App\Services\ContactService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class ContactServiceTest extends TestCase
{
    private ContactService $contactService;
    private IContactRepository $contactRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contactRepository = Mockery::mock(IContactRepository::class);
        $this->contactService = new ContactService($this->contactRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_all_contacts()
    {
        $contacts = new Collection([
            Contact::factory()->make(['id' => 1]),
            Contact::factory()->make(['id' => 2]),
        ]);

        $this->contactRepository
            ->shouldReceive('getAll')
            ->once()
            ->andReturn($contacts);

        $result = $this->contactService->getAllContacts();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    /** @test */
    public function it_returns_contact_by_id()
    {
        $contact = Contact::factory()->make(['id' => 1]);

        $this->contactRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($contact);

        $result = $this->contactService->getContactById(1);

        $this->assertInstanceOf(Contact::class, $result);
        $this->assertEquals(1, $result->id);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_contact()
    {
        $this->contactRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->contactService->getContactById(999);

        $this->assertNull($result);
    }

    /** @test */
    public function it_creates_contact_and_returns_dto()
    {
        $contactData = [
            'first_name' => 'John',
            'second_name' => 'Doe',
            'address' => '123 Main St',
            'description' => 'Need nursing care',
            'phone_number' => '+1234567890',
        ];

        $contact = Contact::factory()->make([
            'id' => 1,
            'first_name' => 'John',
            'second_name' => 'Doe',
            'address' => '123 Main St',
            'description' => 'Need nursing care',
            'phone_number' => '+1234567890',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contactRepository
            ->shouldReceive('create')
            ->with($contactData)
            ->once()
            ->andReturn($contact);

        $result = $this->contactService->createContact($contactData);

        $this->assertInstanceOf(ContactResponseDTO::class, $result);
        $this->assertEquals('John', $result->first_name);
        $this->assertEquals('Doe', $result->second_name);
        $this->assertEquals('123 Main St', $result->address);
        $this->assertEquals('Need nursing care', $result->description);
        $this->assertEquals('+1234567890', $result->phone_number);
    }

    /** @test */
    public function it_deletes_contact_successfully()
    {
        $contact = Contact::factory()->make(['id' => 1]);

        $this->contactRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($contact);

        $this->contactRepository
            ->shouldReceive('delete')
            ->with($contact)
            ->once()
            ->andReturn(true);

        $result = $this->contactService->deleteContact(1);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_deleting_nonexistent_contact()
    {
        $this->contactRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->contactService->deleteContact(999);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_latest_contacts()
    {
        $contacts = new Collection([
            Contact::factory()->make(['id' => 1]),
            Contact::factory()->make(['id' => 2]),
        ]);

        $this->contactRepository
            ->shouldReceive('getLatest')
            ->with(5)
            ->once()
            ->andReturn($contacts);

        $result = $this->contactService->getLatestContacts(5);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    /** @test */
    public function it_returns_contact_count()
    {
        $this->contactRepository
            ->shouldReceive('getCount')
            ->once()
            ->andReturn(10);

        $result = $this->contactService->getContactCount();

        $this->assertEquals(10, $result);
    }

    /** @test */
    public function it_returns_contact_with_details()
    {
        $contact = Contact::factory()->make([
            'id' => 1,
            'first_name' => 'John',
            'second_name' => 'Doe',
            'address' => '123 Main St',
            'description' => 'Need nursing care',
            'phone_number' => '+1234567890',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        $this->contactRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($contact);

        $result = $this->contactService->getContactWithDetails(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('contact', $result);
        $this->assertArrayHasKey('created_at_formatted', $result);
        $this->assertArrayHasKey('days_ago', $result);
        $this->assertGreaterThanOrEqual(0, $result['days_ago']);
        $this->assertLessThanOrEqual(2, $result['days_ago']); // Allow for small time differences
    }

    /** @test */
    public function it_returns_null_for_contact_with_details_when_not_found()
    {
        $this->contactRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->contactService->getContactWithDetails(999);

        $this->assertNull($result);
    }
} 