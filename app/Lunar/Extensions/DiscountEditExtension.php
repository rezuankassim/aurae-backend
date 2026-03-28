<?php

namespace App\Lunar\Extensions;

use App\Models\Discount;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Lunar\Admin\Support\Extending\EditPageExtension;
use Lunar\DiscountTypes\AmountOff;
use Lunar\DiscountTypes\BuyXGetY;
use Lunar\Models\Currency;

class DiscountEditExtension extends EditPageExtension
{
    protected array $requiredBuyXGetYFields = [
        'data.min_qty',
        'data.reward_qty',
        'data.max_reward_qty',
    ];

    public function headerActions(array $actions): array
    {
        return [
            Actions\Action::make('toggle_enabled')
                ->label(function () {
                    $record = $this->caller?->getRecord();

                    return $record?->enabled ? 'Disable' : 'Enable';
                })
                ->icon(function () {
                    $record = $this->caller?->getRecord();

                    return $record?->enabled ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle';
                })
                ->color(function () {
                    $record = $this->caller?->getRecord();

                    return $record?->enabled ? 'danger' : 'success';
                })
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->caller?->getRecord();

                    if (! $record) {
                        return;
                    }

                    if (! $record->enabled) {
                        $errors = $this->getDiscountCompletionErrors($record);

                        if (! empty($errors)) {
                            Notification::make()
                                ->title('Cannot enable discount')
                                ->body('Please complete the following before enabling: '.implode(', ', $errors).'.')
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }
                    }

                    $record->update(['enabled' => ! $record->enabled]);

                    Notification::make()
                        ->title($record->enabled ? 'Discount enabled' : 'Discount disabled')
                        ->success()
                        ->send();
                }),
            ...$actions,
        ];
    }

    protected function getDiscountCompletionErrors(Discount $discount): array
    {
        $errors = [];

        if ($discount->type === BuyXGetY::class) {
            $data = $discount->data ?? [];

            if (empty($data['min_qty'])) {
                $errors[] = 'product quantity is required';
            }

            if (empty($data['reward_qty'])) {
                $errors[] = 'number of free items is required';
            }

            if (empty($data['max_reward_qty'])) {
                $errors[] = 'maximum reward quantity is required';
            }

            if ($discount->discountableConditions()->count() === 0) {
                $errors[] = 'at least one product condition is required';
            }

            if ($discount->discountableRewards()->count() === 0) {
                $errors[] = 'at least one product reward is required';
            }
        }

        if ($discount->type === AmountOff::class) {
            $data = $discount->data ?? [];
            $hasFixedValue = ! empty($data['fixed_value']);
            $hasPercentage = ! empty($data['percentage']);
            $hasFixedValues = ! empty(array_filter($data['fixed_values'] ?? []));

            if (! $hasPercentage && ! $hasFixedValues) {
                $errors[] = 'discount amount (fixed value or percentage) is required';
            }
        }

        if ($discount->channels()->count() === 0) {
            $errors[] = 'at least one channel must be assigned in Availability';
        }

        if ($discount->customerGroups()->count() === 0) {
            $errors[] = 'at least one customer group must be assigned in Availability';
        }

        return $errors;
    }

    public function extendForm(Form $form): Form
    {
        $schema = $form->getComponents(withHidden: true);
        $this->hideHandleField($schema);
        $this->makeFieldsRequired($schema);
        $this->forceFixedValueOnly($schema);
        $this->setMinDateOnStartsAt($schema);

        return $form->schema($schema);
    }

    protected function hideHandleField(array $components): void
    {
        foreach ($components as $component) {
            if ($component instanceof TextInput && $component->getName() === 'handle') {
                $component->hidden(true);
            }

            if (method_exists($component, 'getChildComponents')) {
                $this->hideHandleField($component->getChildComponents());
            }
        }
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
                    $enabledCodes = Currency::enabled()->pluck('code')->all();

                    $filteredInputs = array_filter(
                        $currencyGroup->getChildComponents(),
                        fn ($input) => $input instanceof TextInput
                            && in_array(last(explode('.', $input->getName())), $enabledCodes)
                    );

                    $component->schema([
                        Hidden::make('data.fixed_value')
                            ->default(true)
                            ->afterStateHydrated(fn ($component) => $component->state(true)),
                        Group::make(array_values($filteredInputs))
                            ->columns(count($filteredInputs)),
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
