import { router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import LessonsTab from '@/Pages/Settings/Tabs/LessonsTab';

export default function AcademyTab({
    t,
    buildings = [],
    academyTab = 'halls',
    lessonTab = 'ballet',
    lessons = [],
    lessonTeachers = [],
}) {
    const { auth } = usePage().props;
    const canWrite = auth?.user?.can_write === true;
    const canDelete = auth?.user?.can_delete === true;

    const [showCreateGroup, setShowCreateGroup] = useState(false);
    const [editingGroup, setEditingGroup] = useState(null);
    const [showCreateRoomFor, setShowCreateRoomFor] = useState(null);
    const [editingRoom, setEditingRoom] = useState(null);
    const activeAcademyTab = ['general', 'halls', 'lessons'].includes(academyTab) ? academyTab : 'halls';

    const switchAcademyTab = (nextTab) => {
        if (nextTab === activeAcademyTab) {
            return;
        }

        router.get(route('settings.index'), {
            tab: 'academy',
            academyTab: nextTab,
            ...(nextTab === 'lessons' ? { lessonTab } : {}),
        }, {
            preserveState: false,
            preserveScroll: false,
            replace: true,
        });
    };

    const groupForm = useForm({
        name: '',
        address: '',
        is_active: true,
    });
    const roomForm = useForm({
        academy_building_id: '',
        name: '',
        capacity: '',
        room_type: '',
        is_active: true,
    });

    const openCreateGroup = () => {
        groupForm.setData({ name: '', address: '', is_active: true });
        groupForm.clearErrors();
        setShowCreateGroup(true);
    };

    const openEditGroup = (group) => {
        groupForm.setData({
            name: group.name ?? '',
            address: group.address ?? '',
            is_active: group.is_active === true,
        });
        groupForm.clearErrors();
        setEditingGroup(group);
    };

    const openCreateRoom = (group) => {
        roomForm.setData({
            academy_building_id: group.id,
            name: '',
            capacity: '',
            room_type: '',
            is_active: true,
        });
        roomForm.clearErrors();
        setShowCreateRoomFor(group);
    };

    const openEditRoom = (group, room) => {
        roomForm.setData({
            academy_building_id: group.id,
            name: room.name ?? '',
            capacity: room.capacity ?? '',
            room_type: room.room_type ?? '',
            is_active: room.is_active === true,
        });
        roomForm.clearErrors();
        setEditingRoom({ group, room });
    };

    return (
        <div className="space-y-4">
            <section className="app-widget p-2">
                <nav className="flex flex-wrap gap-1">
                    {['general', 'halls', 'lessons'].map((tabId) => (
                        <button
                            key={tabId}
                            type="button"
                            onClick={() => switchAcademyTab(tabId)}
                            className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                activeAcademyTab === tabId
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            }`}
                        >
                            {tabId === 'general'
                                ? t.academyTabGeneral
                                : tabId === 'halls'
                                    ? t.academyTabHalls
                                    : t.academyTabLessons}
                        </button>
                    ))}
                </nav>
            </section>

            {activeAcademyTab === 'general' && (
                <section className="app-widget p-4">
                    <h2 className="text-base font-semibold text-slate-900">{t.academyTabGeneral}</h2>
                    <p className="mt-2 text-sm text-slate-500">{t.academyGeneralPlaceholder}</p>
                </section>
            )}

            {activeAcademyTab === 'lessons' && (
                <LessonsTab
                    lessonTab={lessonTab}
                    lessons={lessons}
                    teachers={lessonTeachers}
                />
            )}

            {activeAcademyTab === 'halls' && (
                <>
            <section className="app-widget p-4">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">{t.academyHallsTitle}</h2>
                        <p className="mt-1 text-sm text-slate-600">{t.academyHallsDescription}</p>
                    </div>
                    {canWrite && (
                        <button
                            type="button"
                            onClick={openCreateGroup}
                            className="inline-flex h-10 items-center gap-2 rounded-lg bg-[#1A2B44] px-4 text-sm font-semibold text-white hover:bg-[#132033]"
                        >
                            <Plus className="h-4 w-4" />
                            {t.addHallGroup}
                        </button>
                    )}
                </div>
            </section>

            <section className="space-y-3">
                {buildings.length === 0 ? (
                    <div className="app-widget p-4 text-sm text-slate-500">{t.academyHallGroupsEmpty}</div>
                ) : (
                    buildings.map((group) => (
                        <div key={group.id} className="app-widget p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 className="text-base font-semibold text-slate-900">{group.name}</h3>
                                    <p className="text-xs text-slate-500">
                                        {group.address || '—'} • {group.is_active ? t.active : t.no}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {canWrite && (
                                        <>
                                            <button
                                                type="button"
                                                onClick={() => openCreateRoom(group)}
                                                className="inline-flex h-8 items-center gap-1 rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                            >
                                                <Plus className="h-3.5 w-3.5" />
                                                {t.addHall}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => openEditGroup(group)}
                                                className="inline-flex h-8 items-center gap-1 rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                                {t.edit}
                                            </button>
                                        </>
                                    )}
                                    {canDelete && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (!confirm(t.deleteConfirm)) {
                                                    return;
                                                }
                                                router.delete(route('settings.academy.buildings.destroy', group.id), {
                                                    preserveScroll: true,
                                                });
                                            }}
                                            className="inline-flex h-8 items-center gap-1 rounded-md border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100"
                                        >
                                            <Trash2 className="h-3.5 w-3.5" />
                                            {t.delete}
                                        </button>
                                    )}
                                </div>
                            </div>

                            <div className="mt-3 overflow-x-auto rounded-lg border border-slate-200">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th className="px-3 py-2 text-left">{t.fieldName}</th>
                                            <th className="px-3 py-2 text-left">{t.academyHallType}</th>
                                            <th className="px-3 py-2 text-left">{t.academyHallCapacity}</th>
                                            <th className="px-3 py-2 text-left">{t.colActive}</th>
                                            <th className="px-3 py-2 text-left">{t.colActions}</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {group.rooms.length === 0 ? (
                                            <tr>
                                                <td className="px-3 py-4 text-sm text-slate-500" colSpan={5}>
                                                    {t.academyHallsEmpty}
                                                </td>
                                            </tr>
                                        ) : (
                                            group.rooms.map((room) => (
                                                <tr key={room.id}>
                                                    <td className="px-3 py-2">{room.name}</td>
                                                    <td className="px-3 py-2 text-slate-600">{room.room_type || '—'}</td>
                                                    <td className="px-3 py-2 text-slate-600">{room.capacity ?? '—'}</td>
                                                    <td className="px-3 py-2">{room.is_active ? t.yes : t.no}</td>
                                                    <td className="px-3 py-2">
                                                        <div className="flex items-center gap-2">
                                                            {canWrite && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => openEditRoom(group, room)}
                                                                    className="inline-flex h-8 items-center gap-1 rounded-md border border-slate-300 px-3 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                    {t.edit}
                                                                </button>
                                                            )}
                                                            {canDelete && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => {
                                                                        if (!confirm(t.deleteConfirm)) {
                                                                            return;
                                                                        }
                                                                        router.delete(route('settings.academy.rooms.destroy', [group.id, room.id]), {
                                                                            preserveScroll: true,
                                                                        });
                                                                    }}
                                                                    className="inline-flex h-8 items-center gap-1 rounded-md border border-red-200 bg-red-50 px-3 text-xs font-medium text-red-700 hover:bg-red-100"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                    {t.delete}
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ))
                )}
            </section>

            {showCreateGroup && (
                <Modal title={t.addHallGroup} onClose={() => setShowCreateGroup(false)}>
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            groupForm.post(route('settings.academy.buildings.store'), {
                                preserveScroll: true,
                                onSuccess: () => setShowCreateGroup(false),
                            });
                        }}
                    >
                        <GroupFields t={t} form={groupForm} />
                        <Actions t={t} processing={groupForm.processing} onCancel={() => setShowCreateGroup(false)} />
                    </form>
                </Modal>
            )}

            {editingGroup && (
                <Modal title={t.editHallGroup} onClose={() => setEditingGroup(null)}>
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            groupForm.patch(route('settings.academy.buildings.update', editingGroup.id), {
                                preserveScroll: true,
                                onSuccess: () => setEditingGroup(null),
                            });
                        }}
                    >
                        <GroupFields t={t} form={groupForm} />
                        <Actions t={t} processing={groupForm.processing} onCancel={() => setEditingGroup(null)} />
                    </form>
                </Modal>
            )}

            {showCreateRoomFor && (
                <Modal title={`${t.addHall} — ${showCreateRoomFor.name}`} onClose={() => setShowCreateRoomFor(null)}>
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            roomForm.post(route('settings.academy.rooms.store', showCreateRoomFor.id), {
                                preserveScroll: true,
                                onSuccess: () => setShowCreateRoomFor(null),
                            });
                        }}
                    >
                        <RoomFields t={t} form={roomForm} />
                        <Actions t={t} processing={roomForm.processing} onCancel={() => setShowCreateRoomFor(null)} />
                    </form>
                </Modal>
            )}

            {editingRoom && (
                <Modal title={`${t.editHall} — ${editingRoom.room.name}`} onClose={() => setEditingRoom(null)}>
                    <form
                        className="space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            roomForm.patch(route('settings.academy.rooms.update', [editingRoom.group.id, editingRoom.room.id]), {
                                preserveScroll: true,
                                onSuccess: () => setEditingRoom(null),
                            });
                        }}
                    >
                        <RoomFields t={t} form={roomForm} />
                        <Actions t={t} processing={roomForm.processing} onCancel={() => setEditingRoom(null)} />
                    </form>
                </Modal>
            )}
                </>
            )}
        </div>
    );
}

function GroupFields({ t, form }) {
    return (
        <div className="space-y-3">
            <Field label={t.fieldName} error={form.errors.name}>
                <input
                    type="text"
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    className={inputClass}
                />
            </Field>
            <Field label={t.academyGroupAddress} error={form.errors.address}>
                <input
                    type="text"
                    value={form.data.address}
                    onChange={(e) => form.setData('address', e.target.value)}
                    className={inputClass}
                />
            </Field>
            <label className="flex items-center gap-2 text-sm text-slate-700">
                <input
                    type="checkbox"
                    checked={form.data.is_active === true}
                    onChange={(e) => form.setData('is_active', e.target.checked)}
                    className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                {t.active}
            </label>
        </div>
    );
}

function RoomFields({ t, form }) {
    return (
        <div className="space-y-3">
            <Field label={t.fieldName} error={form.errors.name}>
                <input
                    type="text"
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    className={inputClass}
                />
            </Field>
            <Field label={t.academyHallType} error={form.errors.room_type}>
                <input
                    type="text"
                    value={form.data.room_type}
                    onChange={(e) => form.setData('room_type', e.target.value)}
                    className={inputClass}
                />
            </Field>
            <Field label={t.academyHallCapacity} error={form.errors.capacity}>
                <input
                    type="number"
                    min="0"
                    value={form.data.capacity}
                    onChange={(e) => form.setData('capacity', e.target.value)}
                    className={inputClass}
                />
            </Field>
            <label className="flex items-center gap-2 text-sm text-slate-700">
                <input
                    type="checkbox"
                    checked={form.data.is_active === true}
                    onChange={(e) => form.setData('is_active', e.target.checked)}
                    className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                {t.active}
            </label>
        </div>
    );
}

function Actions({ t, processing, onCancel }) {
    return (
        <div className="flex justify-end gap-2">
            <button
                type="button"
                onClick={onCancel}
                className="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
            >
                {t.cancel}
            </button>
            <button
                type="submit"
                disabled={processing}
                className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white hover:bg-[#132033] disabled:opacity-60"
            >
                {t.saveChanges}
            </button>
        </div>
    );
}

function Field({ label, children, error }) {
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </label>
    );
}

function Modal({ title, children, onClose }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4">
            <div className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-slate-200 bg-white p-5 shadow-xl">
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

const inputClass = 'block h-10 w-full rounded-lg border border-slate-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';

