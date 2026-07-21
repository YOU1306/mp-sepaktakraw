<?php

namespace App\Filament\Resources\RegistrationApplications\Tables;

use App\Models\Player;
use App\Models\RegistrationApplication;
use App\Services\RegistrationReviewService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RegistrationApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference_no')->label('Reference')->searchable()->copyable(),
                TextColumn::make('type')->badge()->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('applicant_name')->label('Applicant')->searchable(),
                TextColumn::make('applicant_email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        RegistrationApplication::STATUS_APPROVED => 'success',
                        RegistrationApplication::STATUS_REJECTED, RegistrationApplication::STATUS_EXPIRED => 'danger',
                        RegistrationApplication::STATUS_UNDER_REVIEW => 'warning',
                        RegistrationApplication::STATUS_PENDING_PAYMENT => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucwords(str_replace('_', ' ', $state))),
                TextColumn::make('submitted_at')->dateTime('d M Y, H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    RegistrationApplication::TYPE_INDIVIDUAL => 'Individual',
                    RegistrationApplication::TYPE_FEDERATION => 'Federation',
                    RegistrationApplication::TYPE_CLUB => 'Club',
                ]),
                SelectFilter::make('status')
                    ->options([
                        RegistrationApplication::STATUS_PENDING_PAYMENT => 'Pending payment',
                        RegistrationApplication::STATUS_UNDER_REVIEW => 'Under review',
                        RegistrationApplication::STATUS_APPROVED => 'Approved',
                        RegistrationApplication::STATUS_REJECTED => 'Rejected',
                        RegistrationApplication::STATUS_EXPIRED => 'Expired',
                    ])
                    ->default(RegistrationApplication::STATUS_UNDER_REVIEW),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (RegistrationApplication $record) => 'Application '.$record->reference_no)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->infolist(fn ($schema) => $schema->components([
                        Section::make('Application')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('reference_no')->label('Reference'),
                                TextEntry::make('status')->formatStateUsing(fn ($s) => ucwords(str_replace('_', ' ', $s))),
                                TextEntry::make('type')->formatStateUsing(fn ($s) => ucfirst($s)),
                                TextEntry::make('applicant_name')->label('Applicant'),
                                TextEntry::make('applicant_email')->label('Secretary / applicant email'),
                                TextEntry::make('district.name')->label('District'),
                                TextEntry::make('submitted_at')->dateTime('d M Y, H:i'),
                            ]),
                        Section::make('Player')
                            ->visible(fn (RegistrationApplication $r) => $r->type === RegistrationApplication::TYPE_INDIVIDUAL)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('player.name')->label('Name'),
                                TextEntry::make('player.category')->label('Category')->formatStateUsing(fn ($s) => $s ? ucwords(str_replace('_', ' ', $s)) : '-'),
                                TextEntry::make('player.father_name')->label("Father's name"),
                                TextEntry::make('player.mother_name')->label("Mother's name"),
                                TextEntry::make('player.dob')->label('Date of birth')->date('d M Y'),
                                TextEntry::make('player.sex')->label('Sex')->formatStateUsing(fn ($s) => ucfirst((string) $s)),
                                TextEntry::make('player.email')->label('Email'),
                                TextEntry::make('player.contact_number')->label('Contact'),
                                TextEntry::make('player.address')->label('Address')->columnSpanFull(),
                                TextEntry::make('player_docs')->hiddenLabel()->columnSpanFull()
                                    ->state(fn (RegistrationApplication $r) => self::documentLinks($r->player?->documents))->html(),
                            ]),
                        Section::make('Organisation')
                            ->visible(fn (RegistrationApplication $r) => in_array($r->type, [RegistrationApplication::TYPE_FEDERATION, RegistrationApplication::TYPE_CLUB], true))
                            ->schema([
                                TextEntry::make('org')->hiddenLabel()
                                    ->state(fn (RegistrationApplication $r) => self::organisationSummary($r))->html(),
                            ]),
                        Section::make('Office Bearers')
                            ->visible(fn (RegistrationApplication $r) => in_array($r->type, [RegistrationApplication::TYPE_FEDERATION, RegistrationApplication::TYPE_CLUB], true))
                            ->schema([
                                TextEntry::make('bearers')->hiddenLabel()
                                    ->state(fn (RegistrationApplication $r) => self::officeBearersTable($r))->html(),
                            ]),
                        Section::make('Members')
                            ->visible(fn (RegistrationApplication $r) => $r->type === RegistrationApplication::TYPE_CLUB)
                            ->schema([
                                TextEntry::make('members_list')->hiddenLabel()
                                    ->state(fn (RegistrationApplication $r) => self::membersTable($r))->html(),
                            ]),
                    ])),
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Approve this application? An account will be created and credentials emailed to the applicant / secretary.')
                    ->visible(fn (RegistrationApplication $record) => $record->isUnderReview())
                    ->action(function (RegistrationApplication $record) {
                        RegistrationReviewService::approve($record, auth()->user());
                        Notification::make()->title('Approved. Credentials emailed.')->success()->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RegistrationApplication $record) => $record->isUnderReview())
                    ->schema([
                        Textarea::make('reason')->label('Reason (included in the email)')->required()->maxLength(500),
                    ])
                    ->action(function (array $data, RegistrationApplication $record) {
                        RegistrationReviewService::reject($record, auth()->user(), $data['reason']);
                        Notification::make()->title('Rejected. Applicant notified.')->warning()->send();
                    }),
            ]);
    }

    protected static function documentLinks($documents): string
    {
        $docs = $documents ?? collect();
        if ($docs->isEmpty()) {
            return '<span class="text-gray-500">No documents.</span>';
        }

        return $docs->map(function ($doc) {
            $label = ucwords(str_replace('_', ' ', $doc->kind));

            return '<a class="text-primary-600 underline" target="_blank" href="'.route('documents.show', $doc).'">'.$label.'</a>';
        })->implode(' &nbsp;|&nbsp; ');
    }

    protected static function organisationSummary(RegistrationApplication $r): string
    {
        if ($r->type === RegistrationApplication::TYPE_FEDERATION && $r->federation) {
            $ack = self::documentLinks($r->documents);

            return '<div class="space-y-1"><div><strong>Registration No:</strong> '.e($r->federation->registration_number).'</div>'
                .'<div><strong>Acknowledgement:</strong> '.$ack.'</div></div>';
        }

        if ($r->type === RegistrationApplication::TYPE_CLUB && $r->club) {
            return '<div class="space-y-1">'
                .'<div><strong>Club:</strong> '.e($r->club->club_name).'</div>'
                .'<div><strong>Registration No:</strong> '.e($r->club->registration_number).'</div>'
                .'<div><strong>Place:</strong> '.e($r->club->place).'</div></div>';
        }

        return '<span class="text-gray-500">No details.</span>';
    }

    protected static function officeBearersTable(RegistrationApplication $r): string
    {
        $bearers = $r->officeBearers;
        if ($bearers->isEmpty()) {
            return '<span class="text-gray-500">None.</span>';
        }

        $rows = $bearers->map(function ($b) {
            return '<tr class="border-b border-gray-100">'
                .'<td class="py-1 pr-3">'.e($b->name).'</td>'
                .'<td class="py-1 pr-3">'.e($b->designationLabel()).'</td>'
                .'<td class="py-1 pr-3">'.e($b->contact).'</td>'
                .'<td class="py-1 pr-3">'.e($b->email).'</td>'
                .'<td class="py-1">'.self::documentLinks($b->documents).'</td></tr>';
        })->implode('');

        return '<table class="w-full text-sm"><thead><tr class="text-left text-gray-500 border-b border-gray-200">'
            .'<th class="py-1 pr-3">Name</th><th class="py-1 pr-3">Designation</th><th class="py-1 pr-3">Contact</th><th class="py-1 pr-3">Email</th><th class="py-1">Aadhaar</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table>';
    }

    protected static function membersTable(RegistrationApplication $r): string
    {
        $members = $r->members;
        if ($members->isEmpty()) {
            return '<span class="text-gray-500">None.</span>';
        }

        $rows = $members->map(function (Player $m) {
            $cat = $m->category ? ucwords(str_replace('_', ' ', $m->category)) : '-';

            return '<tr class="border-b border-gray-100">'
                .'<td class="py-1 pr-3">'.e($m->name).'</td>'
                .'<td class="py-1 pr-3">'.e($m->memberRoleLabel()).'</td>'
                .'<td class="py-1 pr-3">'.e($cat).'</td>'
                .'<td class="py-1 pr-3">'.e($m->contact_number).'</td>'
                .'<td class="py-1">'.self::documentLinks($m->documents).'</td></tr>';
        })->implode('');

        return '<table class="w-full text-sm"><thead><tr class="text-left text-gray-500 border-b border-gray-200">'
            .'<th class="py-1 pr-3">Name</th><th class="py-1 pr-3">Role</th><th class="py-1 pr-3">Category</th><th class="py-1 pr-3">Contact</th><th class="py-1">Documents</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table>';
    }
}
