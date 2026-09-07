import { router, useForm, usePage } from '@inertiajs/react';
import { Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

const TEXT = {
    it: {
        tabAub: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Carcano',
        addLesson: 'Aggiungi lezione',
        editLesson: 'Modifica lezione',
        emptyLessons: 'Nessuna lezione. Clicca «Aggiungi lezione» per iniziare.',
        name: 'Nome',
        description: 'Descrizione',
        teachers: 'Insegnanti',
        noTeachers: 'Nessun insegnante disponibile.',
        notAssigned: '—',
        cancel: 'Annulla',
        save: 'Salva',
        add: 'Aggiungi',
        deleteLesson: 'Elimina lezione',
        confirmDeleteTitle: 'Conferma eliminazione',
        confirmDeleteLesson: 'Eliminare questa lezione?',
        confirm: 'Elimina',
    },
    uk: {
        tabAub: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Каркано',
        addLesson: 'Додати урок',
        editLesson: 'Редагувати урок',
        emptyLessons: 'Уроків ще немає. Натисніть «Додати урок».',
        name: 'Назва',
        description: 'Опис',
        teachers: 'Викладачі',
        noTeachers: 'Немає доступних викладачів.',
        notAssigned: '—',
        cancel: 'Скасувати',
        save: 'Зберегти',
        add: 'Додати',
        deleteLesson: 'Видалити урок',
        confirmDeleteTitle: 'Підтвердження видалення',
        confirmDeleteLesson: 'Видалити цей урок?',
        confirm: 'Видалити',
    },
    en: {
        tabAub: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Carcano',
        addLesson: 'Add lesson',
        editLesson: 'Edit lesson',
        emptyLessons: 'No lessons yet. Click «Add lesson» to start.',
        name: 'Name',
        description: 'Description',
        teachers: 'Teachers',
        noTeachers: 'No teachers available.',
        notAssigned: '—',
        cancel: 'Cancel',
        save: 'Save',
        add: 'Add',
        deleteLesson: 'Delete lesson',
        confirmDeleteTitle: 'Confirm deletion',
        confirmDeleteLesson: 'Delete this lesson?',
        confirm: 'Delete',
    },
    ru: {
        tabAub: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Каркано',
        addLesson: 'Добавить урок',
        editLesson: 'Редактировать урок',
        emptyLessons: 'Уроков пока нет. Нажмите «Добавить урок».',
        name: 'Название',
        description: 'Описание',
        teachers: 'Преподаватели',
        noTeachers: 'Нет доступных преподавателей.',
        notAssigned: '—',
        cancel: 'Отмена',
        save: 'Сохранить',
        add: 'Добавить',
        deleteLesson: 'Удалить урок',
        confirmDeleteTitle: 'Подтверждение удаления',
        confirmDeleteLesson: 'Удалить этот урок?',
        confirm: 'Удалить',
    },
};

const TABS = ['ballet', 'tam', 'carcano'];

function Modal({ title, children, onClose, wide = false }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
            <div className={`w-full ${wide ? 'max-w-lg' : 'max-w-md'} max-h-[90vh] overflow-y-auto rounded-xl border border-slate-200 bg-white p-5 shadow-xl`}>
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-base font-semibold text-slate-900">{title}</h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    >
                        <X className="h-4 w-4" />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

function MultiSelectList({ label, items, selectedIds, onToggle, emptyText, error }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-700">{label}</label>
            {items.length === 0 ? (
                <p className="rounded-lg border border-dashed border-slate-200 px-3 py-2 text-sm text-slate-500">
                    {emptyText}
                </p>
            ) : (
                <div className="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                    {items.map((item) => {
                        const checked = selectedIds.includes(item.id);
                        return (
                            <label
                                key={item.id}
                                className={`flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm transition ${
                                    checked ? 'bg-indigo-50 text-indigo-900' : 'hover:bg-slate-50'
                                }`}
                            >
                                <input
                                    type="checkbox"
                                    checked={checked}
                                    onChange={() => onToggle(item.id)}
                                    className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span className="truncate">{item.name}</span>
                            </label>
                        );
                    })}
                </div>
            )}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

function formatNames(items, fallback) {
    if (!items?.length) {
        return fallback;
    }

    return items.map((item) => item.name).join(', ');
}

export default function LessonsTab({
    lessonTab = 'ballet',
    lessons = [],
    teachers = [],
}) {
    const { locale = 'it', auth } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;
    const canDelete = auth?.user?.can_delete === true;
    const canWrite = auth?.user?.can_write === true;
    const activeTab = TABS.includes(lessonTab) ? lessonTab : 'ballet';

    const [showCreateModal, setShowCreateModal] = useState(false);
    const [editingLesson, setEditingLesson] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);

    const createForm = useForm({
        discipline: activeTab,
        name: '',
        description: '',
        teacher_ids: [],
    });

    const editForm = useForm({
        name: '',
        description: '',
        teacher_ids: [],
    });

    const settingsLessonsUrl = (nextLessonTab = activeTab) => route('settings.index', {
        tab: 'academy',
        academyTab: 'lessons',
        lessonTab: nextLessonTab,
    });

    const switchTab = (nextTab) => {
        if (nextTab === activeTab) {
            return;
        }

        router.get(settingsLessonsUrl(nextTab), {}, {
            preserveState: false,
            preserveScroll: false,
            replace: true,
        });
    };

    const toggleSelection = (form, field, id) => {
        const current = form.data[field] ?? [];
        form.setData(
            field,
            current.includes(id) ? current.filter((itemId) => itemId !== id) : [...current, id],
        );
    };

    const openCreateModal = () => {
        createForm.setData({
            discipline: activeTab,
            name: '',
            description: '',
            teacher_ids: [],
        });
        createForm.clearErrors();
        setShowCreateModal(true);
    };

    const openEditModal = (lesson) => {
        editForm.setData({
            name: lesson.name ?? '',
            description: lesson.description ?? '',
            teacher_ids: lesson.teacher_ids ?? [],
        });
        editForm.clearErrors();
        setEditingLesson(lesson);
    };

    const submitCreate = (e) => {
        e.preventDefault();
        createForm.post(route('lessons.store'), {
            preserveScroll: true,
            onSuccess: () => setShowCreateModal(false),
        });
    };

    const submitEdit = (e) => {
        e.preventDefault();
        if (!editingLesson) {
            return;
        }

        editForm.patch(route('lessons.update', editingLesson.id), {
            preserveScroll: true,
            onSuccess: () => setEditingLesson(null),
        });
    };

    const confirmDelete = () => {
        if (!pendingDelete) {
            return;
        }

        router.delete(route('lessons.destroy', pendingDelete.id), {
            preserveScroll: true,
            onFinish: () => setPendingDelete(null),
        });
    };

    const tabLabels = { ballet: t.tabAub, tam: t.tabTam, carcano: t.tabCarcano };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <nav
                    className="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 shadow-sm"
                    role="tablist"
                >
                    {TABS.map((tabId) => {
                        const isActive = activeTab === tabId;
                        return (
                            <button
                                key={tabId}
                                type="button"
                                role="tab"
                                aria-selected={isActive}
                                onClick={() => switchTab(tabId)}
                                className={`min-w-[7rem] rounded-lg px-5 py-2.5 text-sm font-semibold transition ${
                                    isActive
                                        ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                {tabLabels[tabId]}
                            </button>
                        );
                    })}
                </nav>

                {canWrite && (
                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="inline-flex items-center gap-2 rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white hover:bg-[#132033]"
                    >
                        <Plus className="h-4 w-4" />
                        {t.addLesson}
                    </button>
                )}
            </div>

            <section className="app-widget overflow-hidden p-0">
                {lessons.length === 0 ? (
                    <p className="p-6 text-sm text-slate-500">{t.emptyLessons}</p>
                ) : (
                    <div className="divide-y divide-slate-100">
                        {lessons.map((lesson) => (
                            <div
                                key={lesson.id}
                                role="button"
                                tabIndex={0}
                                onClick={() => openEditModal(lesson)}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter' || e.key === ' ') {
                                        e.preventDefault();
                                        openEditModal(lesson);
                                    }
                                }}
                                className="flex cursor-pointer items-start gap-3 px-4 py-3 hover:bg-slate-50/60"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="font-medium text-slate-900">{lesson.name}</div>
                                    {lesson.description && (
                                        <p className="mt-1 text-sm text-slate-600">{lesson.description}</p>
                                    )}
                                    <div className="mt-2 text-xs text-slate-500">
                                        <p>
                                            <span className="font-medium text-slate-600">{t.teachers}:</span>{' '}
                                            {formatNames(lesson.teachers, t.notAssigned)}
                                        </p>
                                    </div>
                                </div>
                                {canDelete && (
                                    <div className="flex shrink-0 items-center gap-1">
                                        <button
                                            type="button"
                                            title={t.deleteLesson}
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                setPendingDelete(lesson);
                                            }}
                                            className="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </section>

            {showCreateModal && (
                <Modal title={t.addLesson} onClose={() => setShowCreateModal(false)} wide>
                    <form onSubmit={submitCreate} className="space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">{t.name}</label>
                            <input
                                type="text"
                                value={createForm.data.name}
                                onChange={(e) => createForm.setData('name', e.target.value)}
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                autoFocus
                            />
                            {createForm.errors.name && (
                                <p className="mt-1 text-xs text-red-600">{createForm.errors.name}</p>
                            )}
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">{t.description}</label>
                            <textarea
                                value={createForm.data.description}
                                onChange={(e) => createForm.setData('description', e.target.value)}
                                rows={3}
                                className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                            />
                            {createForm.errors.description && (
                                <p className="mt-1 text-xs text-red-600">{createForm.errors.description}</p>
                            )}
                        </div>
                        <MultiSelectList
                            label={t.teachers}
                            items={teachers}
                            selectedIds={createForm.data.teacher_ids}
                            onToggle={(id) => toggleSelection(createForm, 'teacher_ids', id)}
                            emptyText={t.noTeachers}
                            error={createForm.errors.teacher_ids}
                        />
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowCreateModal(false)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={createForm.processing}
                                className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white hover:bg-[#132033] disabled:opacity-60"
                            >
                                {t.add}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {editingLesson && (
                <Modal title={t.editLesson} onClose={() => setEditingLesson(null)} wide>
                    <form onSubmit={submitEdit} className="space-y-4">
                        <fieldset disabled={!canWrite} className={!canWrite ? 'space-y-4 opacity-90' : 'space-y-4'}>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">{t.name}</label>
                                <input
                                    type="text"
                                    value={editForm.data.name}
                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    autoFocus
                                />
                                {editForm.errors.name && (
                                    <p className="mt-1 text-xs text-red-600">{editForm.errors.name}</p>
                                )}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium text-slate-700">{t.description}</label>
                                <textarea
                                    value={editForm.data.description}
                                    onChange={(e) => editForm.setData('description', e.target.value)}
                                    rows={3}
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                />
                                {editForm.errors.description && (
                                    <p className="mt-1 text-xs text-red-600">{editForm.errors.description}</p>
                                )}
                            </div>
                            <MultiSelectList
                                label={t.teachers}
                                items={teachers}
                                selectedIds={editForm.data.teacher_ids}
                                onToggle={(id) => toggleSelection(editForm, 'teacher_ids', id)}
                                emptyText={t.noTeachers}
                                error={editForm.errors.teacher_ids}
                            />
                        </fieldset>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setEditingLesson(null)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                {t.cancel}
                            </button>
                            {canWrite && (
                                <button
                                    type="submit"
                                    disabled={editForm.processing}
                                    className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white hover:bg-[#132033] disabled:opacity-60"
                                >
                                    {t.save}
                                </button>
                            )}
                        </div>
                    </form>
                </Modal>
            )}

            {pendingDelete && (
                <Modal title={t.confirmDeleteTitle} onClose={() => setPendingDelete(null)}>
                    <p className="text-sm text-slate-600">{t.confirmDeleteLesson}</p>
                    <p className="mt-2 font-medium text-slate-900">{pendingDelete.name}</p>
                    <div className="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setPendingDelete(null)}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            {t.cancel}
                        </button>
                        <button
                            type="button"
                            onClick={confirmDelete}
                            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                        >
                            {t.confirm}
                        </button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
