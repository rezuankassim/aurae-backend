<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Lunar\Admin\Support\Extending\EditPageExtension;
use Lunar\DiscountTypes\BuyXGetY;

class DiscountEditExtension extends EditPageExtension
{
    protected array $requiredBuyXGetYFields = [
        'data.min_qty',
        'data.reward_qty',
        'data.max_reward_qty',
    ];

    public function extendForm(Form $form): Form
    {
        $schema = $form->getComponents();
        $this->makeFieldsRequired($schema);
        $this->hidePercentageField($schema);
        $this->setMinDateOnStartsAt($schema);

        return $form->schema($schema);
    }

    protected function makeFieldsRequired(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof TextInput && in_array($component->getName(), $this->requiredBuyXGetYFields)) {
                $component->required(fn (Get $get) => $get('type') === BuyXGetY::class);
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->makeFieldsRequired($component->getChildComponents());
            }
        }
    }

    public function beforeFill(array $data): array
    {
        $data['data']['fixed_value'] = true;

        return $data;
    }

    public function beforeSave(array $data): array
    {
        $data['data']['fixed_value'] = true;

        return $data;
    }

    protected function hidePercentageField(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof TextInput && $component->getName() === 'data.percentage') {
                $component->hidden();
            }

            if ($component instanceof Toggle && $component->getName() === 'data.fixed_value') {
                $component->default(true)
                    ->hidden()
                    ->dehydrated(true)
                    ->afterStateHydrated(fn (Toggle $c) => $c->state(true));
            }

            if ($component instanceof Group && $this->isFixedValuesGroup($component)) {
                $component->visible(true);
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->hidePercentageField($component->getChildComponents());
            }
        }
    }

    protected function isFixedValuesGroup(Group $group): bool
    {
        foreach ($group->getChildComponents() as $child) {
            if ($child instanceof TextInput && str_starts_with($child->getName(), 'data.fixed_values.')) {
                return true;
            }
        }

        return false;
    }

    protected function setMinDateOnStartsAt(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof DateTimePicker && $component->getName() === 'starts_at') {
                $component->minDate(function () {
                    $record = $this->caller?->getRecord();
                    $originalStartsAt = $record?->starts_at;
                    $today = now()->startOfDay();

                    if ($originalStartsAt && $originalStartsAt->isBefore($today)) {
                        return $originalStartsAt;
                    }

                    return $today;
                });
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->setMinDateOnStartsAt($component->getChildComponents());
            }
        }
    }
}
