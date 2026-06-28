<?php

namespace App\Filament\DocenteResources\Submissions\Tables;

use App\Models\Grade;
use App\Models\Submission;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('evaluation_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('student_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('attempt')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('late_flag')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('grade')
                    ->label('Grade')
                    ->authorize('grade')
                    ->form([
                        TextInput::make('score')
                            ->numeric(),
                        Textarea::make('feedback')
                            ->columnSpanFull(),
                    ])
                    ->action(function (Submission $record, array $data): void {
                        $user = Auth::user();
                        if (! $user instanceof \App\Models\User) {
                            abort(403);
                        }

                        Grade::updateOrCreate(
                            [
                                'evaluation_id' => $record->evaluation_id,
                                'student_id' => $record->student_id,
                            ],
                            [
                                'score' => $data['score'] ?? null,
                                'feedback' => $data['feedback'] ?? null,
                                'graded_by' => $user->getAuthIdentifier(),
                            ],
                        );
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
