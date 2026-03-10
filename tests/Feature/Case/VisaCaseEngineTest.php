<?php

namespace Tests\Feature\Case;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\CaseCheckpointStatus;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Models\VisaCaseCheckpoint;
use App\Modules\Case\Models\VisaCaseRule;
use App\Modules\Case\Models\VisaFormFieldMapping;
use App\Modules\Case\Services\FranceFormService;
use App\Modules\Case\Services\VisaCaseEngineService;
use App\Modules\Client\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisaCaseEngineTest extends TestCase
{
    use RefreshDatabase;

    private VisaCaseRule $rule;
    private Agency $agency;
    private Client $client;

    private function makeCase(array $attrs = []): VisaCase
    {
        return VisaCase::factory()->create(array_merge([
            'agency_id'  => $this->agency->id,
            'client_id'  => $this->client->id,
        ], $attrs));
    }

    protected function setUp(): void
    {
        parent::setUp();

        [$this->agency, ] = $this->createAgencyWithOwner();
        $this->client = $this->createClient($this->agency, [
            'name'  => 'Pulat Khaliulin',
        ]);

        // Создаём тестовое правило для FR/tourist
        $this->rule = VisaCaseRule::create([
            'country_code'     => 'FR',
            'visa_type'        => 'tourist',
            'visa_subtype'     => 'short_stay',
            'applicant_type'   => 'adult',
            'embassy_platform' => 'france_visas',
            'submission_method'=> 'vfs',
            'appointment_required' => true,
            'biometrics_required'  => true,
            'workflow_steps'       => ['identity', 'employment', 'previous_visa', 'stay_passport', 'contacts_funding', 'documents'],
        ]);

        // Чекпоинты
        VisaCaseCheckpoint::create([
            'visa_case_rule_id' => $this->rule->id,
            'stage'             => 'qualification',
            'slug'              => 'passport_valid',
            'title'             => 'Паспорт проверен',
            'check_type'        => 'manual',
            'is_blocking'       => true,
            'display_order'     => 1,
        ]);

        VisaCaseCheckpoint::create([
            'visa_case_rule_id' => $this->rule->id,
            'stage'             => 'documents',
            'slug'              => 'all_docs',
            'title'             => 'Все документы загружены',
            'check_type'        => 'auto_document',
            'is_blocking'       => true,
            'display_order'     => 1,
        ]);

        // Поля анкеты (пара полей для теста)
        VisaFormFieldMapping::create([
            'visa_case_rule_id' => $this->rule->id,
            'step_number'       => 1,
            'step_title'        => 'Your identity',
            'field_key'         => 'surname',
            'field_label'       => 'Surname(s)',
            'field_type'        => 'text',
            'mapping_source'    => 'client.last_name',
            'transform_rule'    => 'uppercase',
            'is_required'       => true,
            'display_order'     => 1,
        ]);

        VisaFormFieldMapping::create([
            'visa_case_rule_id' => $this->rule->id,
            'step_number'       => 1,
            'step_title'        => 'Your identity',
            'field_key'         => 'first_name',
            'field_label'       => 'First name(s)',
            'field_type'        => 'text',
            'mapping_source'    => 'client.first_name',
            'transform_rule'    => 'uppercase',
            'is_required'       => true,
            'display_order'     => 2,
        ]);

        VisaFormFieldMapping::create([
            'visa_case_rule_id' => $this->rule->id,
            'step_number'       => 4,
            'step_title'        => 'Your stay',
            'field_key'         => 'entry_date',
            'field_label'       => 'Date of arrival',
            'field_type'        => 'date',
            'mapping_source'    => 'case.travel_date',
            'is_required'       => true,
            'display_order'     => 1,
        ]);
    }

    // =========================================================================
    // resolveRule
    // =========================================================================

    public function test_resolve_rule_exact_match(): void
    {
        $found = VisaCaseRule::resolveRule('FR', 'tourist', 'short_stay', 'adult');
        $this->assertNotNull($found);
        $this->assertEquals($this->rule->id, $found->id);
    }

    public function test_resolve_rule_fallback_without_subtype(): void
    {
        // Правило без subtype для fallback
        $general = VisaCaseRule::create([
            'country_code'      => 'FR',
            'visa_type'         => 'tourist',
            'visa_subtype'      => null,
            'applicant_type'    => 'adult',
            'embassy_platform'  => 'france_visas',
            'submission_method' => 'vfs',
            'workflow_steps'    => ['identity'],
        ]);

        // Запрос с неизвестным subtype -> fallback на null subtype
        $found = VisaCaseRule::resolveRule('FR', 'tourist', 'long_stay', 'adult');
        $this->assertNotNull($found);
        $this->assertEquals($general->id, $found->id);
    }

    public function test_resolve_rule_no_match(): void
    {
        $found = VisaCaseRule::resolveRule('XX', 'work');
        $this->assertNull($found);
    }

    // =========================================================================
    // initializeEngine
    // =========================================================================

    public function test_initialize_engine_creates_checkpoints(): void
    {
        $case = $this->makeCase([
            'country_code' => 'FR',
            'visa_type'    => 'tourist',
        ]);

        $case->visa_subtype   = 'short_stay';
        $case->applicant_type = 'adult';
        $case->save();

        $ok = VisaCaseEngineService::initializeEngine($case);

        $this->assertTrue($ok);
        $case->refresh();

        $this->assertEquals($this->rule->id, $case->visa_case_rule_id);
        $this->assertEquals('france_visas', $case->embassy_platform);
        $this->assertTrue($case->biometrics_required);

        // 2 чекпоинта
        $this->assertCount(2, CaseCheckpointStatus::where('case_id', $case->id)->get());
    }

    public function test_initialize_engine_returns_false_for_unknown_country(): void
    {
        $case = $this->makeCase([
            'country_code' => 'XX',
            'visa_type'    => 'work',
        ]);

        $ok = VisaCaseEngineService::initializeEngine($case);
        $this->assertFalse($ok);
    }

    // =========================================================================
    // refreshCaseReadiness
    // =========================================================================

    public function test_readiness_zero_when_nothing_done(): void
    {
        $case = $this->makeCase([
            'country_code' => 'FR',
            'visa_type'    => 'tourist',
        ]);
        $case->visa_subtype   = 'short_stay';
        $case->applicant_type = 'adult';
        $case->save();

        VisaCaseEngineService::initializeEngine($case);
        $case->refresh();

        // Чекпоинты не выполнены (0/40), документов нет в чеклисте (40/40), формы не заполнены (0/20) = 40
        $this->assertEquals(40, $case->readiness_score);
        $this->assertNotEmpty($case->missing_items);
        $this->assertNotNull($case->next_action);
    }

    public function test_readiness_increases_after_checkpoint(): void
    {
        $case = $this->makeCase([
            'country_code' => 'FR',
            'visa_type'    => 'tourist',
        ]);
        $case->visa_subtype   = 'short_stay';
        $case->applicant_type = 'adult';
        $case->save();

        VisaCaseEngineService::initializeEngine($case);
        $before = $case->fresh()->readiness_score;

        // Отмечаем 1 из 2 чекпоинтов
        $cp = VisaCaseCheckpoint::where('visa_case_rule_id', $this->rule->id)->first();
        VisaCaseEngineService::toggleCheckpoint($case, $cp->id, true);

        $after = $case->fresh()->readiness_score;
        $this->assertGreaterThan($before, $after);
    }

    // =========================================================================
    // FranceFormService
    // =========================================================================

    public function test_get_form_fields_returns_steps(): void
    {
        $case = $this->makeCase([
            'country_code' => 'FR',
            'visa_type'    => 'tourist',
        ]);
        $case->visa_subtype   = 'short_stay';
        $case->applicant_type = 'adult';
        $case->save();

        VisaCaseEngineService::initializeEngine($case);

        $steps = FranceFormService::getFormFields($case);

        $this->assertCount(2, $steps); // step 1 и step 4
        $this->assertEquals(1, $steps[0]['step']);
        $this->assertEquals('Your identity', $steps[0]['title']);
        $this->assertCount(2, $steps[0]['fields']); // surname + first_name
    }

    public function test_save_form_step_updates_form_data(): void
    {
        $case = $this->makeCase([
            'country_code' => 'FR',
            'visa_type'    => 'tourist',
        ]);
        $case->visa_subtype   = 'short_stay';
        $case->applicant_type = 'adult';
        $case->save();

        VisaCaseEngineService::initializeEngine($case);

        FranceFormService::saveFormStep($case, 1, [
            'surname'    => 'KHALIULIN',
            'first_name' => 'PULAT',
        ]);

        $case->refresh();
        $this->assertEquals('KHALIULIN', $case->form_data['surname']);
        $this->assertEquals('PULAT', $case->form_data['first_name']);
    }

    public function test_form_progress(): void
    {
        $case = $this->makeCase([
            'country_code' => 'FR',
            'visa_type'    => 'tourist',
        ]);
        $case->visa_subtype   = 'short_stay';
        $case->applicant_type = 'adult';
        $case->save();

        VisaCaseEngineService::initializeEngine($case);

        // Заполняем 2 из 3 required полей
        FranceFormService::saveFormStep($case, 1, [
            'surname'    => 'KHALIULIN',
            'first_name' => 'PULAT',
        ]);

        $progress = FranceFormService::getFormProgress($case);
        $this->assertEquals(3, $progress['total_required']);
        $this->assertEquals(2, $progress['total_filled']);
        $this->assertEquals(67, $progress['percent']); // 2/3 = 66.67 -> 67
    }

    public function test_toggle_checkpoint(): void
    {
        $case = $this->makeCase([
            'country_code' => 'FR',
            'visa_type'    => 'tourist',
        ]);
        $case->visa_subtype   = 'short_stay';
        $case->applicant_type = 'adult';
        $case->save();

        VisaCaseEngineService::initializeEngine($case);

        $cp = VisaCaseCheckpoint::where('visa_case_rule_id', $this->rule->id)->first();

        // Complete
        $status = VisaCaseEngineService::toggleCheckpoint($case, $cp->id, true, null, 'Проверен');
        $this->assertTrue($status->is_completed);
        $this->assertNotNull($status->completed_at);
        $this->assertEquals('Проверен', $status->notes);

        // Uncomplete
        $status = VisaCaseEngineService::toggleCheckpoint($case, $cp->id, false);
        $this->assertFalse($status->is_completed);
        $this->assertNull($status->completed_at);
    }
}
