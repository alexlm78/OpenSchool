<x-filament-panels::page>
    <div class="mb-4 flex items-center justify-between no-print">
        <div>
            <p class="text-sm text-gray-500">
                {{ __('boletin.issued_for') }}: <strong>{{ $guardianName }}</strong>
            </p>
            <p class="text-xs text-gray-400">
                {{ __('boletin.generated_at') }}: {{ $reportGeneratedAt }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <x-filament::button icon="heroicon-o-printer" color="primary" onclick="window.print()">
                {{ __('boletin.print_pdf') }}
            </x-filament::button>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 p-4 print:border-0 print:p-0">
        <h3 class="mb-2 text-lg font-semibold">{{ __('boletin.overall_gpa') }}</h3>
        <div class="flex items-center gap-4">
            <div class="text-3xl font-bold @if($overallGpaColor === 'success') text-green-600 @elseif($overallGpaColor === 'warning') text-yellow-600 @elseif($overallGpaColor === 'danger') text-red-600 @else text-gray-500 @endif">
                {{ $overallGpaLabel === __('widgets.gpa_no_data') ? '—' : $overallGpaPercent . '%' }}
            </div>
            <x-filament::badge color="{{ $overallGpaColor }}">
                {{ $overallGpaLabel }}
            </x-filament::badge>
        </div>
    </div>

    <div class="space-y-8">
        @foreach ($studentReports as $report)
            @php
                $student = $report['student'];
                $enrollments = $report['enrollments'];
                $studentGpaPercent = $report['studentGpaPercent'];
                $studentGpaColor = 'gray';
                $studentGpaLabel = __('widgets.gpa_no_data');
                if ($studentGpaPercent !== null) {
                    if ($studentGpaPercent >= 70) { $studentGpaColor = 'success'; $studentGpaLabel = __('widgets.gpa_approved'); }
                    elseif ($studentGpaPercent >= 50) { $studentGpaColor = 'warning'; $studentGpaLabel = __('widgets.gpa_recovery'); }
                    else { $studentGpaColor = 'danger'; $studentGpaLabel = __('widgets.gpa_failing'); }
                }
            @endphp
            <section class="rounded-xl border border-gray-200 p-5 print:border-0 print:break-inside-avoid print:p-0 print:pt-2">
                <header class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3 print:border-b-1">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ $student?->user?->getAttributeValue('name') ?? __('general.unknown') }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ __('boletin.student_id') }}: {{ $student->getAttributeValue('student_id') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold @if($studentGpaColor === 'success') text-green-600 @elseif($studentGpaColor === 'warning') text-yellow-600 @elseif($studentGpaColor === 'danger') text-red-600 @else text-gray-500 @endif">
                            {{ $studentGpaPercent === null ? '—' : $studentGpaPercent . '%' }}
                        </div>
                        <x-filament::badge size="sm" color="{{ $studentGpaColor }}">
                            {{ $studentGpaLabel }}
                        </x-filament::badge>
                    </div>
                </header>

                @if (count($enrollments) === 0)
                    <div class="py-6 text-center text-gray-500">
                        {{ __('boletin.no_enrollments') }}
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach ($enrollments as $row)
                            @php
                                $enrollment = $row['enrollment'];
                                $grades = $row['grades'];
                                $gpaPercent = $row['gpaPercent'];
                                $gpaLabel = $row['gpaLabel'];
                                $gpaColor = $row['gpaColor'];
                            @endphp
                            <article class="rounded-lg border border-gray-100 p-4 print:break-inside-avoid">
                                <div class="mb-3 flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-800">
                                            {{ $enrollment?->courseOffering?->courseTemplate?->getAttributeValue('name') ?? __('general.unknown') }}
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            {{ __('boletin.section') }}: {{ $enrollment?->courseOffering?->getAttributeValue('section_name') ?? '—' }}
                                            · {{ __('boletin.status') }}:
                                            <span class="font-medium">{{ __(sprintf('enrollments.status_%s', (string) $enrollment->getAttributeValue('status'))) }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xl font-bold @if($gpaColor === 'success') text-green-600 @elseif($gpaColor === 'warning') text-yellow-600 @elseif($gpaColor === 'danger') text-red-600 @else text-gray-400 @endif">
                                            {{ $gpaPercent === null ? '—' : $gpaPercent . '%' }}
                                        </div>
                                        <x-filament::badge size="sm" color="{{ $gpaColor }}">
                                            {{ $gpaLabel }}
                                        </x-filament::badge>
                                    </div>
                                </div>

                                @if ($grades->count() === 0)
                                    <div class="py-3 text-sm text-gray-500">
                                        {{ __('boletin.no_grades_yet') }}
                                    </div>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                                            <thead class="bg-gray-50 print:bg-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">
                                                        {{ __('boletin.evaluation') }}
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">
                                                        {{ __('boletin.category') }}
                                                    </th>
                                                    <th class="px-3 py-2 text-left font-medium text-gray-600">
                                                        {{ __('boletin.date') }}
                                                    </th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-600">
                                                        {{ __('boletin.score') }}
                                                    </th>
                                                    <th class="px-3 py-2 text-right font-medium text-gray-600">
                                                        {{ __('boletin.percent') }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($grades as $grade)
                                                    @php
                                                        $score = (float) $grade->getAttributeValue('score');
                                                        $max = $grade?->evaluation?->getAttributeValue('max_score');
                                                        $pct = null;
                                                        if ($max !== null && (float) $max > 0) {
                                                            $pct = round($score / (float) $max * 100, 1);
                                                        }
                                                        $rowColor = 'gray';
                                                        if ($pct !== null) {
                                                            if ($pct >= 70) $rowColor = 'success';
                                                            elseif ($pct >= 50) $rowColor = 'warning';
                                                            else $rowColor = 'danger';
                                                        }
                                                    @endphp
                                                    <tr class="print:bg-transparent">
                                                        <td class="px-3 py-2">
                                                            {{ $grade?->evaluation?->getAttributeValue('title') ?? __('general.unknown') }}
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <x-filament::badge size="sm">
                                                                {{ __(sprintf('evaluations.category_%s', (string) ($grade?->evaluation?->getAttributeValue('category') ?? 'other'))) }}
                                                            </x-filament::badge>
                                                        </td>
                                                        <td class="px-3 py-2 text-gray-600">
                                                            {{ $grade->getAttributeValue('created_at') ? (\Carbon\Carbon::parse($grade->getAttributeValue('created_at'))->format('M d, Y')) : '—' }}
                                                        </td>
                                                        <td class="px-3 py-2 text-right font-mono">
                                                            {{ $score }} / {{ $max ?? '—' }}
                                                        </td>
                                                        <td class="px-3 py-2 text-right">
                                                            @if($pct !== null)
                                                                <x-filament::badge color="{{ $rowColor }}">
                                                                    {{ $pct }}%
                                                                </x-filament::badge>
                                                            @else
                                                                <span class="text-gray-400">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>

<style>
@media print {
    body { background: white; }
    .no-print { display: none !important; }
    [x-data] { display: initial !important; }
    nav, header, footer, aside, .fi-topbar, .fi-sidebar, .fi-tenant-menu, .fi-user-menu, [class*="topbar"], [class*="sidebar"] { display: none !important; }
    main { padding: 0 !important; }
    .fi-page, .fi-main, .fi-panel { padding: 0 !important; margin: 0 !important; }
    section { page-break-inside: avoid; }
    article { page-break-inside: avoid; }
    table { page-break-inside: auto; }
    tr { page-break-inside: avoid; }
}
</style>
