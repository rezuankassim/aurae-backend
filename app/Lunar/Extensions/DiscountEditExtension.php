<?php

namespace App\Lunar\Extensions;

use Filament\Forms\Components\TextInput;
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

    protected function hidePercentageField(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof TextInput && $component->getName() === 'data.percentage') {
                $component->hidden();
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->hidePercentageField($component->getChildComponents());
            }
        }
    }
}
