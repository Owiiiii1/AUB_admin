import { router } from '@inertiajs/react';
import { Settings } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

function timeToMinutes(time) {
    if (!time) {
        return 0;
    }

    const [hours, minutes] = time.split(':').map(Number);

    return (hours * 60) + minutes;
}

function minutesToTime(totalMinutes) {
    const normalized = Math.max(0, Math.min(totalMinutes, (24 * 60) - 1));
    const hours = Math.floor(normalized / 60);
    const minutes = normalized % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

function addMinutesToTime(time, minutes) {
    return minutesToTime(timeToMinutes(time) + Number(minutes));
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

function lessonPatchPayload(lesson, overrides = {}) {
    return {
        lesson_date: lesson.lesson_date,
        starts_at: overrides.starts_at ?? lesson.starts_at,
        ends_at: overrides.ends_at ?? lesson.ends_at,
        academy_building_id: lesson.academy_building_id,
        academy_room_id: lesson.academy_room_id,
        course_group_id: lesson.course_group_id ?? '',
        teacher_id: overrides.teacher_id ?? lesson.teacher_id ?? '',
        lesson_id: lesson.lesson_id ?? '',
        title: lesson.title ?? '',
        notes: lesson.notes ?? '',
        color: lesson.color ?? '',
        status: lesson.status ?? 'scheduled',
    };
}

export default function TimelineLessonBlock({
    lesson,
    weekId,
    positionStyle,
    appearance,
    gridStart,
    gridSpan,
    gridHeight,
    canEdit = false,
    draggable = false,
    onDragStart,
    t,
}) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [editTeacherId, setEditTeacherId] = useState(String(lesson.teacher_id ?? ''));
    const [editMinutes, setEditMinutes] = useState(String(lesson.duration_minutes ?? 60));
    const [saving, setSaving] = useState(false);
    const [tooltipVisible, setTooltipVisible] = useState(false);
    const [tooltipPosition, setTooltipPosition] = useState({ top: 0, left: 0 });
    const [menuPosition, setMenuPosition] = useState({ top: 0, left: 0 });
    const menuPanelRef = useRef(null);
    const settingsButtonRef = useRef(null);
    const blockRef = useRef(null);

    const hasBudgetControls = (lesson.available_teachers?.length ?? 0) > 0;
    const courseBadge = resolveCourseBadge(lesson.course_name);
    const tooltipRows = [
        `${t.start}: ${lesson.starts_at}`,
        `${t.end}: ${lesson.ends_at}`,
        `${t.group}: ${lesson.group_name || '—'}`,
        `${t.subject}: ${lesson.subject_name || '—'}`,
        `${t.teacher}: ${lesson.teacher_name || '—'}`,
        `${t.building}: ${lesson.building_name || '—'}`,
        `${t.room}: ${lesson.room_name || '—'}`,
    ];

    useEffect(() => {
        if (!menuOpen) {
            return undefined;
        }

        const handlePointerDown = (event) => {
            const target = event.target;
            if (
                menuPanelRef.current?.contains(target)
                || settingsButtonRef.current?.contains(target)
            ) {
                return;
            }

            setMenuOpen(false);
        };

        document.addEventListener('pointerdown', handlePointerDown);

        return () => document.removeEventListener('pointerdown', handlePointerDown);
    }, [menuOpen]);

    const updateMenuPosition = () => {
        if (!settingsButtonRef.current) {
            return;
        }

        const rect = settingsButtonRef.current.getBoundingClientRect();
        const menuWidth = 176;
        const left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8));

        setMenuPosition({
            top: rect.bottom + 4,
            left,
        });
    };

    useEffect(() => {
        if (!menuOpen) {
            return undefined;
        }

        updateMenuPosition();

        const handleScroll = () => setMenuOpen(false);
        const handleResize = () => updateMenuPosition();

        window.addEventListener('scroll', handleScroll, true);
        window.addEventListener('resize', handleResize);

        return () => {
            window.removeEventListener('scroll', handleScroll, true);
            window.removeEventListener('resize', handleResize);
        };
    }, [menuOpen]);

    useEffect(() => {
        setEditTeacherId(String(lesson.teacher_id ?? ''));
        setEditMinutes(String(lesson.duration_minutes ?? 60));
    }, [lesson.id, lesson.teacher_id, lesson.duration_minutes]);

    const updateTooltipPosition = () => {
        if (!blockRef.current) {
            return;
        }

        const rect = blockRef.current.getBoundingClientRect();
        setTooltipPosition({
            top: rect.bottom + 4,
            left: rect.left,
        });
    };

    const showTooltip = () => {
        if (menuOpen) {
            return;
        }

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

    const selectedBudget = useMemo(() => {
        const teacherId = Number(editTeacherId);

        return lesson.teacher_budgets?.find((budget) => budget.teacher_id === teacherId) ?? null;
    }, [editTeacherId, lesson.teacher_budgets]);

    const maxMinutes = useMemo(() => {
        if (!selectedBudget) {
            return Number(lesson.duration_minutes ?? 0);
        }

        if (Number(editTeacherId) === Number(lesson.teacher_id)) {
            return Number(lesson.duration_minutes ?? 0) + Number(lesson.remaining_minutes ?? 0);
        }

        return Number(selectedBudget.remaining_minutes ?? 0);
    }, [editTeacherId, lesson.duration_minutes, lesson.remaining_minutes, lesson.teacher_id, selectedBudget]);

    const minutesHint = selectedBudget
        ? t.blockMinutesHint
            .replace('{remaining}', String(selectedBudget.remaining_minutes ?? 0))
            .replace('{weekly}', String(selectedBudget.weekly_minutes ?? 0))
        : '';

    const openMenu = (event) => {
        event.stopPropagation();
        event.preventDefault();
        setTooltipVisible(false);
        setEditTeacherId(String(lesson.teacher_id ?? ''));
        setEditMinutes(String(lesson.duration_minutes ?? 60));

        if (menuOpen) {
            setMenuOpen(false);
            return;
        }

        updateMenuPosition();
        setMenuOpen(true);
    };

    const applySettings = (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (saving) {
            return;
        }

        const minutes = Number(editMinutes);
        if (!Number.isFinite(minutes) || minutes < 30 || minutes > maxMinutes || minutes % 30 !== 0) {
            return;
        }

        setSaving(true);
        setMenuOpen(false);

        router.patch(route('weekly-schedule.lessons.update', [weekId, lesson.id]), lessonPatchPayload(lesson, {
            teacher_id: editTeacherId,
            ends_at: addMinutesToTime(lesson.starts_at, minutes),
        }), {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const removeLesson = (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (saving) {
            return;
        }

        setSaving(true);
        setMenuOpen(false);

        router.delete(route('weekly-schedule.lessons.destroy', [weekId, lesson.id]), {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const handleResizeStart = (event) => {
        event.stopPropagation();
        event.preventDefault();

        if (!canEdit || gridHeight <= 0) {
            return;
        }

        const startY = event.clientY;
        const initialStart = timeToMinutes(lesson.starts_at);
        const endMinutes = timeToMinutes(lesson.ends_at);
        const maxDurationMinutes = Number(lesson.duration_minutes ?? 0) + Number(lesson.remaining_minutes ?? 0);
        const minStart = timeToMinutes(gridStart);
        const target = event.currentTarget;

        target.setPointerCapture(event.pointerId);

        const handleMove = (moveEvent) => {
            moveEvent.preventDefault();
            moveEvent.stopPropagation();

            const deltaPx = moveEvent.clientY - startY;
            const deltaMinutes = Math.round(((deltaPx / gridHeight) * gridSpan) / 30) * 30;
            let nextStart = initialStart + deltaMinutes;
            nextStart = Math.max(minStart, Math.min(nextStart, endMinutes - 30));

            const nextDurationMinutes = endMinutes - nextStart;
            if (nextDurationMinutes > maxDurationMinutes) {
                nextStart = endMinutes - maxDurationMinutes;
            }

            target.dataset.previewStart = minutesToTime(nextStart);
        };

        const handleUp = (upEvent) => {
            upEvent.preventDefault();
            upEvent.stopPropagation();

            target.releasePointerCapture(upEvent.pointerId);
            document.removeEventListener('pointermove', handleMove);
            document.removeEventListener('pointerup', handleUp);

            const previewStart = target.dataset.previewStart;
            delete target.dataset.previewStart;

            if (!previewStart || previewStart === lesson.starts_at) {
                return;
            }

            router.patch(route('weekly-schedule.lessons.update', [weekId, lesson.id]), lessonPatchPayload(lesson, {
                starts_at: previewStart,
            }), {
                preserveScroll: true,
            });
        };

        document.addEventListener('pointermove', handleMove);
        document.addEventListener('pointerup', handleUp);
    };

    return (
        <div
            ref={blockRef}
            style={{ ...positionStyle, ...appearance.style }}
            onMouseEnter={showTooltip}
            onMouseLeave={hideTooltip}
            className={`group absolute inset-x-0.5 z-10 flex flex-col overflow-hidden rounded-md rounded-tl-none border text-left text-[10px] leading-tight transition hover:brightness-95 ${appearance.className}`}
        >
            {canEdit && (
                <div
                    role="presentation"
                    onPointerDown={handleResizeStart}
                    className="absolute left-0 top-0 z-20 h-4 w-4 cursor-n-resize"
                    title={t.blockResizeStart}
                >
                    <svg viewBox="0 0 16 16" className="h-full w-full text-black/25" aria-hidden="true">
                        <path d="M0 0 L12 0 L0 12 Z" fill="currentColor" />
                    </svg>
                </div>
            )}

            {canEdit && hasBudgetControls && (
                <div className="absolute right-0 top-0 z-30">
                    <button
                        ref={settingsButtonRef}
                        type="button"
                        onClick={openMenu}
                        className="rounded p-0.5 text-current/70 hover:bg-black/10 hover:text-current"
                        aria-label={t.blockSettings}
                    >
                        <Settings className="h-3 w-3" />
                    </button>
                </div>
            )}

            <div
                className={`relative shrink-0 border-b border-black/15 bg-black/10 py-0.5 text-center text-[9px] font-bold uppercase tracking-wide ${hasBudgetControls ? 'pr-5 pl-1.5' : 'px-1.5'}`}
            >
                {courseBadge}
            </div>

            <div
                draggable={draggable}
                onDragStart={(event) => {
                    event.stopPropagation();
                    onDragStart?.(event, lesson);
                }}
                className={`flex min-h-0 flex-1 flex-col overflow-hidden px-1.5 py-0.5 text-left leading-tight ${draggable ? 'cursor-grab active:cursor-grabbing' : ''}`}
            >
                <div className="shrink-0 text-[9px]">
                    <div className="min-w-0 break-words whitespace-normal font-bold">
                        {lesson.group_name || '—'}
                    </div>
                </div>
                <div className="flex min-h-0 flex-1 items-center justify-center break-words whitespace-normal text-center text-[10px] font-bold opacity-90">
                    {lesson.subject_name || '—'}
                </div>
            </div>

            {menuOpen && typeof document !== 'undefined' && createPortal(
                <div
                    ref={menuPanelRef}
                    className="fixed z-[10000] w-44 rounded-lg border border-slate-300 bg-white p-2 text-[11px] text-slate-700 shadow-2xl"
                    style={{
                        top: menuPosition.top,
                        left: menuPosition.left,
                    }}
                    onClick={(event) => event.stopPropagation()}
                >
                    <form onSubmit={applySettings} className="space-y-2">
                        <label className="block space-y-1">
                            <span className="font-medium text-slate-600">{t.dropTeacherLabel}</span>
                            <select
                                value={editTeacherId}
                                onChange={(event) => setEditTeacherId(event.target.value)}
                                className="w-full rounded border border-slate-200 px-2 py-1 text-[11px]"
                            >
                                {(lesson.available_teachers ?? []).map((teacher) => (
                                    <option key={teacher.id} value={teacher.id}>
                                        {teacher.name}
                                    </option>
                                ))}
                            </select>
                        </label>

                        <label className="block space-y-1">
                            <span className="font-medium text-slate-600">{t.blockMinutesLabel}</span>
                            <input
                                type="number"
                                min="30"
                                step="30"
                                max={maxMinutes}
                                value={editMinutes}
                                onChange={(event) => setEditMinutes(event.target.value)}
                                className="w-full rounded border border-slate-200 px-2 py-1 text-[11px]"
                            />
                            {minutesHint && (
                                <span className="block text-[10px] text-slate-500">{minutesHint}</span>
                            )}
                        </label>

                        <button
                            type="submit"
                            disabled={saving}
                            className="w-full rounded bg-slate-900 px-2 py-1 text-[11px] font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                        >
                            {t.save}
                        </button>
                        <button
                            type="button"
                            onClick={removeLesson}
                            disabled={saving}
                            className="w-full rounded border border-red-200 bg-red-50 px-2 py-1 text-[11px] font-medium text-red-700 hover:bg-red-100 disabled:opacity-60"
                        >
                            {t.remove}
                        </button>
                    </form>
                </div>,
                document.body,
            )}

            {tooltipVisible && !menuOpen && typeof document !== 'undefined' && createPortal(
                <div
                    className="pointer-events-none fixed z-[9999] min-w-[180px] max-w-[260px] overflow-hidden rounded-md border border-slate-300 bg-white text-[10px] text-slate-800 shadow-2xl"
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
        </div>
    );
}
