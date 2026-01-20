<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Information')  // Изменение данных о заказе в админ панели
                    ->schema([
                        TextInput::make('id')
                            ->label('ID')
                            ->disabled(),
                        TextInput::make('total_price')
                            ->label('Total Price')
                            ->suffix('₽')
                            ->disabled(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending_payment' => 'Pending Payment',
                                'paid' => 'Paid',
                                'canceled' => 'Canceled',
                            ])
                            ->required()
                            ->native(false),
                    ])->columns(2),

                Section::make('Items') // Связка таблицы заказов и содержание заказа
                    ->schema([
                        Repeater::make('orderItems')
                            ->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->label('Product')
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->debounce(1)
                                    ->required(),

                                TextInput::make('price_at_purchase')
                                    ->label('Price')
                                    ->numeric()
                                    ->suffix('₽')
                                    ->required(),
                            ])->columns(3)->addable(false)->deletable(false)->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table // Отображение данных во вкладке заказы
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable(),
                TextColumn::make('status')->label('Status')->badge()->color(fn (string $state): string => match ($state) {
                    'paid', 'succeeded' => 'success',
                    'pending_payment', 'pending' => 'warning',
                    'canceled', => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('total_price')->label('Total Price')->sortable(),
                TextColumn::make('created_at')->label('Created at')->sortable()->dateTime('d.m.Y H:i')->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter status')
                    ->options([
                        'pending_payment' => 'Pending Payment',
                        'paid' => 'Paid',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    // Считаем заказы со статусом 'pending_payment'
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_payment')->count();
    }

    // Если заказов больше 10 - красный, иначе - оранжевый
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending_payment')->count() > 10
            ? 'danger'
            : 'warning';
    }
}
