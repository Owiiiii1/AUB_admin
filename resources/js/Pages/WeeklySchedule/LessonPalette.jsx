import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const PALETTE_CARD_HEIGHT_PX = 84;
const PALETTE_CARD_GAP_PX = 8;
const PALETTE_MAX_ROWS = 3;
const PALETTE_CARDS_MAX_HEIGHT_PX = (PALETTE_CARD_HEIGHT_PX * PALETTE_MAX_ROWS)
    + (PALETTE_CARD_GAP_PX * (PALETTE_MAX_ROWS - 1));

function hexToRgba(hexColor, alpha) {
    const normalized = hexColor?.replace('#', '') ?? '';
    if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return null;
    }

    const r = parseInt(normalized.slice(0, 2), 16);
    const g = parseInt(normalized.slice(2, 4), 16);
    const b = parseInt(normalized.slice(4, 6), 16);

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function resolveCourseBadge(courseName) {
    const normalized = String(courseName ?? '').trim().toLowerCase();
    if (normalized.includes('tam')) {
        return 'TAM';
    }
    if (normalized.includes('carcano') || normalized.includes('каркан')) {
        return 'Carcano';
    }

    return 'AUB';
}

function PaletteLessonCard({ card, t, disabled, onDragStart }) {
    const cardRef = useRef(null);
    const [tooltipVisible, setTooltipVisible] = useState(false);
    const [tooltipPosition, setTooltipPosition] = useState({ top: 0, left: 0 });

    const cardColor = card.group_color || '#6366f1';
    const courseBadge = resolveCourseBadge(card.course_name);

    const tooltipRows = useMemo(() => {
        const rows = [
            `${t.course}: ${card.course_name || '—'}`,
            `${t.discipline}: ${card.discipline || '—'}`,
            `${t.group}: ${card.group_name || '—'}`,
            `${t.subject}: ${card.lesson_name || '—'}`,
            `${t.teacher}: ${card.teacher_name || '—'}`,
            `${t.weeklyTotal}: ${card.weekly_hours} ${t.paletteHoursShort} (${card.weekly_minutes} ${t.minutes})`,
            `${t.scheduledTotal}: ${card.scheduled_hours} ${t.paletteHoursShort} (${card.scheduled_minutes} ${t.minutes})`,
            `${t.remainingTotal}: ${card.remaining_hours} ${t.paletteHoursShort} (${card.remaining_minutes} ${t.minutes})`,
        ];

        if (card.duration_minutes) {
            rows.push(`${t.sessionDuration}: ${card.duration_minutes} ${t.minutes}`);
        }

        if (!disabled) {
            rows.push(t.paletteDragHint);
        }

        return rows;
    }, [card, disabled, t]);

    const updateTooltipPosition = () => {
        if (!cardRef.current) {
            return;
        }

        const rect = cardRef.current.getBoundingClientRect();
        setTooltipPosition({
            top: rect.bottom + 4,
            left: rect.left,
        });
    };

    const showTooltip = () => {
        updateTooltipPosition();
        setTooltipVisible(true);
    };

    const hideTooltip = () => {
        setTooltipVisible(false);
    };

    useEffect(() => {
        if (!tooltipVisible) {
            return undefined;
        }

        const handleReposition = () => updateTooltipPosition();
        const handleScroll = () => setTooltipVisible(false);

        window.addEventListener('scroll', handleScroll, true);
        window.addEventListener('resize', handleReposition);

        return () => {
            window.removeEventListener('scroll', handleScroll, true);
            window.removeEventListener('resize', handleReposition);
        };
    }, [tooltipVisible]);

    const handleDragStart = (event) => {
        hideTooltip();
        onDragStart(event, card);
    };

    return (
        <>
            <div
                ref={cardRef}
                draggable={!disabled}
                onDragStart={handleDragStart}
                onMouseEnter={showTooltip}
                onMouseLeave={hideTooltip}
                className={`relative w-[168px] rounded-lg rounded-tl-none border px-2.5 py-2 shadow-sm transition ${
                    disabled
                        ? 'cursor-not-allowed border-slate-200 bg-slate-50 opacity-60'
                        : card.remaining_hours > 0
                            ? 'cursor-grab border-slate-200 bg-white active:cursor-grabbing hover:border-slate-300'
                            : 'cursor-grab border-slate-200 bg-slate-50 active:cursor-grabbing hover:border-slate-300'
                }`}
                style={{
                    height: `${PALETTE_CARD_HEIGHT_PX}px`,
                    borderLeftWidth: '4px',
                    borderLeftColor: cardColor,
                    backgroundColor: hexToRgba(cardColor, 0.08) ?? undefined,
                }}
            >
                <div className="absolute left-0 top-0 h-3.5 w-3.5 text-black/25" aria-hidden="true">
                    <svg viewBox="0 0 16 16" className="h-full w-full">
                        <path d="M0 0 L12 0 L0 12 Z" fill="currentColor" />
                    </svg>
                </div>
                <div className="truncate text-xs font-semibold text-slate-900">{card.lesson_name}</div>
                <div className="truncate text-[11px] text-slate-600">{card.group_name}</div>
                <div className="truncate text-[11px] text-slate-500">{card.teacher_name}</div>
                <div className="mt-1.5 flex items-center justify-between gap-1 text-[10px]">
                    <span className="font-medium text-slate-600">
                        {card.scheduled_hours}/{card.weekly_hours} {t.paletteHoursShort}
                    </span>
                    {card.remaining_hours > 0 && (
                        <span className="rounded-full bg-amber-100 px-1.5 py-0.5 font-semibold text-amber-800">
                            {card.remaining_hours} {t.paletteLeftShort}
                        </span>
                    )}
                </div>
            </div>

            {tooltipVisible && typeof document !== 'undefined' && createPortal(
                <div
                    className="pointer-events-none fixed z-[9999] min-w-[200px] max-w-[280px] overflow-hidden rounded-md border border-slate-300 bg-white text-[10px] text-slate-800 shadow-2xl"
                    style={{
                        top: tooltipPosition.top,
                        left: tooltipPosition.left,
                    }}
                >
                    <div className="border-b border-slate-200 bg-white px-2 py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-900">
                        {courseBadge}
                    </div>
                    <div className="space-y-0.5 bg-white px-2 py-1.5">
                        {tooltipRows.map((row) => (
                            <div key={row} className="whitespace-normal break-words">{row}</div>
                        ))}
                    </div>
                </div>,
                document.body,
            )}
        </>
    );
}

export default function LessonPalette({
    t,
    groups = [],
    cards = [],
    filter = 'all',
    onFilterChange,
    disabled = false,
    allowDropScheduled = false,
    onDropScheduled,
}) {
    const [isDropTargetActive, setIsDropTargetActive] = useState(false);

    const unassignedCards = useMemo(
        () => cards.filter((card) => Number(card.scheduled_minutes ?? 0) === 0 && Number(card.remaining_minutes ?? 0) > 0),
        [cards],
    );

    const filteredCards = useMemo(() => {
        if (filter === 'all') {
            return unassignedCards;
        }

        if (filter === 'unassigned') {
            return unassignedCards;
        }

        return unassignedCards.filter((card) => String(card.academy_class_id) === String(filter));
    }, [unassignedCards, filter]);

    const handleDragStart = (event, card) => {
        if (disabled) {
            event.preventDefault();
            return;
        }

        if (event.dataTransfer?.setDragImage && event.currentTarget instanceof HTMLElement) {
            event.dataTransfer.setDragImage(event.currentTarget, 0, 0);
        }

        event.dataTransfer.setData('application/x-aub-drag-type', 'card');
        event.dataTransfer.setData('application/json', JSON.stringify(card));
        event.dataTransfer.effectAllowed = 'copyMove';
    };

    const handleDragOverPalette = (event) => {
        if (!allowDropScheduled || disabled) {
            return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        if (!isDropTargetActive) {
            setIsDropTargetActive(true);
        }
    };

    const handleDragLeavePalette = (event) => {
        if (!allowDropScheduled || disabled) {
            return;
        }

        if (!event.currentTarget.contains(event.relatedTarget)) {
            setIsDropTargetActive(false);
        }
    };

    const handleDropPalette = (event) => {
        if (!allowDropScheduled || disabled) {
            return;
        }

        event.preventDefault();

        const dragType = event.dataTransfer.getData('application/x-aub-drag-type');
        if (dragType !== 'scheduled') {
            setIsDropTargetActive(false);
            return;
        }
        setIsDropTargetActive(false);

        const payloadRaw = event.dataTransfer.getData('application/json');
        if (!payloadRaw) {
            return;
        }

        try {
            const payload = JSON.parse(payloadRaw);
            onDropScheduled?.(payload);
        } catch {
            // ignore invalid payload
        }
    };

    return (
        <section
            onDragOver={handleDragOverPalette}
            onDragLeave={handleDragLeavePalette}
            onDrop={handleDropPalette}
            className={`app-widget p-4 transition ${
                isDropTargetActive ? 'ring-2 ring-inset ring-indigo-400 bg-indigo-50/40' : ''
            }`}
        >
            <div className="flex flex-wrap items-center gap-3">
                <label className="flex min-w-[220px] flex-1 items-center gap-2">
                    <span className="text-sm font-medium text-slate-700">{t.paletteGroupFilter}</span>
                    <select
                        value={filter}
                        onChange={(event) => onFilterChange(event.target.value)}
                        className="block h-10 min-w-0 flex-1 rounded-lg border border-slate-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                    >
                        <option value="all">{t.paletteFilterAll}</option>
                        <option value="unassigned">{t.paletteFilterUnassigned}</option>
                        {groups.map((group) => (
                            <option key={group.id} value={group.id}>
                                {group.name}
                                {group.course_name ? ` (${group.course_name})` : ''}
                            </option>
                        ))}
                    </select>
                </label>
                <span className="text-xs text-slate-500">
                    {filteredCards.length} {t.paletteCardsCount}
                </span>
            </div>

            {filteredCards.length === 0 ? (
                <p className="mt-3 text-sm text-slate-500">{t.paletteEmpty}</p>
            ) : (
                <div
                    className="mt-3 overflow-y-auto pr-1"
                    style={{ maxHeight: `${PALETTE_CARDS_MAX_HEIGHT_PX}px` }}
                >
                    <div className="flex flex-wrap gap-2">
                        {filteredCards.map((card) => (
                            <PaletteLessonCard
                                key={card.key}
                                card={card}
                                t={t}
                                disabled={disabled}
                                onDragStart={handleDragStart}
                            />
                        ))}
                    </div>
                </div>
            )}
        </section>
    );
}
