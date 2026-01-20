<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Product card')->schema([
                    TextInput::make('name')->label('Name')->required()->maxLength(255),
                    TextArea::make('description')->label('Description')->required()->rows(5),

                    Section::make()->schema([
                        TextInput::make('price')->label('Price')->label('Price')->required()->numeric()->minValue(0)->prefix('₽'),
                        TextInput::make('stock')->label('Stock')->numeric()->minValue(0)->default(0)->required(),
                    ])->columns(2),

                    FileUpload::make('image')->label('Image')->image()->directory('products')->visibility('public'), // Папка сохранения: storage/app/public/products
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('description')->label('Description')->searchable()->limit(25),
                ImageColumn::make('image')->label('Image')->circular(),
                TextColumn::make('price')->label('Price'),
                TextColumn::make('stock')->label('Stock')->numeric(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
