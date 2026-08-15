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
                    ->label(__('School ID'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('evaluation_id')
                    ->label(__('Evaluation'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('student_id')
                    ->label(__('Student'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label(__('Submitted At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->searchable(),
                TextColumn::make('attempt')
                    ->label(__('Attempt'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('late_flag')
                    ->label(__('Late Flag'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('grade')
                    ->label(__('Grade'))
                    ->authorize('grade')
                    ->form([
                        TextInput::make('score')
                            ->label(__('Score'))
                            ->numeric(),
                        Textarea::make('feedback')
                            ->label(__('Feedback'))
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
