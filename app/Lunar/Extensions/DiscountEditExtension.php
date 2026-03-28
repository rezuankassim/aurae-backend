<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
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
        $this->forceFixedValueOnly($schema);
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

    protected function forceFixedValueOnly(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof Section) {
                $children = $component->getChildComponents();
                $isAmountOffSection = false;
                $currencyGroup = null;

                foreach ($children as $child) {
                    if ($child instanceof Toggle && $child->getName() === 'data.fixed_value') {
                        $isAmountOffSection = true;
                    }

                    if ($child instanceof Group) {
                        $currencyGroup = $child;
                    }
                }

                if ($isAmountOffSection && $currencyGroup) {
                    $currencyGroup->visible(true);

                    $component->schema([
                        Hidden::make('data.fixed_value')
                            ->default(true)
                            ->afterStateHydrated(fn ($c) => $c->state(true)),
                        $currencyGroup,
                    ]);

                    continue;
                }
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->forceFixedValueOnly($component->getChildComponents());
            }
        }
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
