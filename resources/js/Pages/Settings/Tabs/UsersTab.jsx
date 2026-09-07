import { useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

export default function UsersTab({ t, users = [], roleOptions = [] }) {
    const { auth } = usePage().props;
    const me = auth?.user;
    const canDelete = me?.can_delete === true;
    const canWrite = me?.can_write === true;
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

    const defaultRoleId = roleOptions[0]?.id ?? '';
    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: defaultRoleId,
        can_delete: false,
        can_write: false,
    });
    const editForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: defaultRoleId,
        can_delete: false,
        can_write: false,
    });
    const deleteForm = useForm({ confirm: '' });

    const formatCreated = (iso) => {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleString();
        } catch {
            return iso;
        }
    };

    const openEditModal = (user) => {
        setEditingUser(user);
        setShowDeleteConfirm(false);
        editForm.setData({
            name: user.name ?? '',
            email: user.email ?? '',
            password: '',
            password_confirmation: '',
            role_id: user.role_id ?? defaultRoleId,
            can_delete: user.can_delete === true,
            can_write: user.can_write === true,
        });
        editForm.clearErrors();
        deleteForm.reset();
        deleteForm.clearErrors();
    };

    const closeEditModal = () => {
        setEditingUser(null);
        setShowDeleteConfirm(false);
        editForm.reset();
        editForm.clearErrors();
        deleteForm.reset();
        deleteForm.clearErrors();
    };

    return (
        <>
            <div className="app-widget p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">{t.usersTitle}</h2>
                        <p className="mt-1 text-sm text-slate-600">{t.usersDescription}</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setShowCreateModal(true)}
                        disabled={!canWrite}
                        className="inline-flex h-10 items-center gap-2 rounded-lg bg-[#1A2B44] px-4 text-sm font-semibold text-white transition hover:bg-[#132033] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Plus className="h-4 w-4" />
                        {t.addUser}
                    </button>
                </div>

                <div className="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th className="px-4 py-3 text-left font-semibold">{t.colName}</th>
                                <th className="px-4 py-3 text-left font-semibold">{t.colEmail}</th>
                                <th className="px-4 py-3 text-left font-semibold">{t.colRole}</th>
                                <th className="px-4 py-3 text-left font-semibold">{t.colCreated}</th>
                                <th className="px-4 py-3 text-left font-semibold">{t.colActions}</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 text-slate-700">
                            {users.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-400">
                                        {t.empty}
                                    </td>
                                </tr>
                            ) : (
                                users.map((u) => (
                                    <tr
                                        key={u.id}
                                        className="cursor-pointer hover:bg-slate-50/60"
                                        onClick={() => openEditModal(u)}
                                    >
                                        <td className="px-4 py-3 font-medium text-slate-900">{u.name}</td>
                                        <td className="px-4 py-3">{u.email}</td>
                                        <td className="px-4 py-3 text-slate-600">{u.role_name ?? '—'}</td>
                                        <td className="px-4 py-3 text-slate-500">{formatCreated(u.created_at)}</td>
                                        <td className="px-4 py-3">
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    openEditModal(u);
                                                }}
                                                className="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700 transition hover:bg-slate-100"
                                            >
                                                <Pencil className="h-3.5 w-3.5" />
                                                {t.edit}
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {showCreateModal && (
                <Modal title={t.modalTitle} onClose={() => {
                    setShowCreateModal(false);
                    createForm.reset();
                    createForm.clearErrors();
                }}>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            createForm.post(route('settings.users.store'), {
                                preserveScroll: true,
                                onSuccess: () => {
                                    createForm.reset();
                                    setShowCreateModal(false);
                                },
                            });
                        }}
                        className="space-y-4"
                    >
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Field form={createForm} field="name" label={t.fieldName} />
                            <Field form={createForm} field="email" label={t.fieldEmail} type="email" />
                            <RoleField form={createForm} roles={roleOptions} label={t.fieldRole} />
                            <Field form={createForm} field="password" label={t.fieldPassword} type="password" />
                            <Field form={createForm} field="password_confirmation" label={t.fieldPasswordConfirm} type="password" />
                            <CanDeleteField form={createForm} label={t.fieldCanDelete} />
                            <CanWriteField form={createForm} label={t.fieldCanWrite} />
                        </div>
                        <div className="flex justify-end gap-2">
                            <button type="button" onClick={() => setShowCreateModal(false)} className="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                {t.cancel}
                            </button>
                            <button type="submit" disabled={createForm.processing || !canWrite} className="inline-flex h-10 items-center gap-2 rounded-lg bg-[#1A2B44] px-4 text-sm font-semibold text-white transition hover:bg-[#132033] disabled:opacity-60">
                                <Plus className="h-4 w-4" />
                                {t.save}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {editingUser && (
                <Modal title={t.editModalTitle} onClose={closeEditModal}>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            editForm.patch(route('settings.users.update', editingUser.id), {
                                preserveScroll: true,
                                onSuccess: closeEditModal,
                            });
                        }}
                        className="space-y-4"
                    >
                        <fieldset disabled={!canWrite} className={!canWrite ? 'opacity-90' : undefined}>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Field form={editForm} field="name" label={t.fieldName} />
                            <Field form={editForm} field="email" label={t.fieldEmail} type="email" />
                            <RoleField form={editForm} roles={roleOptions} label={t.fieldRole} />
                            <Field form={editForm} field="password" label={t.fieldPassword} type="password" />
                            <Field form={editForm} field="password_confirmation" label={t.fieldPasswordConfirm} type="password" />
                            <CanDeleteField form={editForm} label={t.fieldCanDelete} />
                            <CanWriteField form={editForm} label={t.fieldCanWrite} />
                        </div>
                        </fieldset>
                        <div className="flex justify-end gap-2">
                            <button type="button" onClick={closeEditModal} className="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                {t.cancel}
                            </button>
                            <button type="submit" disabled={editForm.processing || !canWrite} className="inline-flex h-10 items-center gap-2 rounded-lg bg-[#1A2B44] px-4 text-sm font-semibold text-white transition hover:bg-[#132033] disabled:opacity-60">
                                <Pencil className="h-4 w-4" />
                                {t.update}
                            </button>
                        </div>
                    </form>

                    <div className="mt-5 border-t border-slate-200 pt-4">
                        {Number(editingUser.id) === Number(me?.id) ? (
                            <p className="text-sm text-slate-500">{t.cannotDeleteSelf}</p>
                        ) : !canDelete ? (
                            <p className="text-sm text-slate-500">{t.noDeletePermission}</p>
                        ) : !showDeleteConfirm ? (
                            <button
                                type="button"
                                onClick={() => setShowDeleteConfirm(true)}
                                className="inline-flex h-10 items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                            >
                                <Trash2 className="h-4 w-4" />
                                {t.deleteUser}
                            </button>
                        ) : (
                            <form
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    if (deleteForm.data.confirm !== 'DELETE') {
                                        deleteForm.setError('confirm', 'Type DELETE to continue.');
                                        return;
                                    }
                                    deleteForm.delete(route('settings.users.destroy', editingUser.id), {
                                        preserveScroll: true,
                                        onSuccess: closeEditModal,
                                    });
                                }}
                                className="space-y-3"
                            >
                                <div>
                                    <p className="text-sm font-semibold text-red-700">{t.deleteConfirmTitle}</p>
                                    <p className="mt-1 text-sm text-slate-600">{t.deleteConfirmHint}</p>
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-slate-600">{t.deleteConfirmLabel}</label>
                                    <input
                                        type="text"
                                        value={deleteForm.data.confirm}
                                        onChange={(e) => deleteForm.setData('confirm', e.target.value)}
                                        placeholder={t.deleteConfirmPlaceholder}
                                        className="block h-11 w-full rounded-lg border border-red-200 px-3 text-sm shadow-sm focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-100"
                                    />
                                    <ErrorText message={deleteForm.errors.confirm} />
                                    <ErrorText message={deleteForm.errors.user_delete} />
                                </div>
                                <div className="flex justify-end gap-2">
                                    <button type="button" onClick={() => { setShowDeleteConfirm(false); deleteForm.reset(); deleteForm.clearErrors(); }} className="inline-flex h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                        {t.cancel}
                                    </button>
                                    <button type="submit" disabled={deleteForm.processing} className="inline-flex h-10 items-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-60">
                                        <Trash2 className="h-4 w-4" />
                                        {deleteForm.processing ? t.deleting : t.deleteUser}
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </Modal>
            )}
        </>
    );
}

function Modal({ title, children, onClose }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4">
            <div className="w-full max-w-xl rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <div className="flex items-start justify-between">
                    <h3 className="text-base font-semibold text-slate-900">{title}</h3>
                    <button type="button" onClick={onClose} className="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <X className="h-4 w-4" />
                    </button>
                </div>
                <div className="mt-4">{children}</div>
            </div>
        </div>
    );
}

function RoleField({ form, roles, label }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-600">{label}</label>
            <select
                value={form.data.role_id ?? ''}
                onChange={(e) => form.setData('role_id', e.target.value)}
                className="block h-11 w-full rounded-lg border border-slate-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
            >
                {roles.map((role) => (
                    <option key={role.id} value={role.id}>{role.name}</option>
                ))}
            </select>
            <ErrorText message={form.errors.role_id} />
        </div>
    );
}

function Field({ form, field, label, type = 'text' }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-600">{label}</label>
            <input
                type={type}
                value={form.data[field]}
                onChange={(e) => form.setData(field, e.target.value)}
                className="block h-11 w-full rounded-lg border border-slate-300 px-3 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
            />
            <ErrorText message={form.errors[field]} />
        </div>
    );
}

function CanDeleteField({ form, label }) {
    return (
        <div className="md:col-span-2">
            <label className="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                <input
                    type="checkbox"
                    checked={form.data.can_delete === true}
                    onChange={(e) => form.setData('can_delete', e.target.checked)}
                    className="h-4 w-4 rounded border-slate-300 text-[#1A2B44] focus:ring-[#1A2B44]"
                />
                {label}
            </label>
            <ErrorText message={form.errors.can_delete} />
        </div>
    );
}

function CanWriteField({ form, label }) {
    return (
        <div className="md:col-span-2">
            <label className="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                <input
                    type="checkbox"
                    checked={form.data.can_write === true}
                    onChange={(e) => form.setData('can_write', e.target.checked)}
                    className="h-4 w-4 rounded border-slate-300 text-[#1A2B44] focus:ring-[#1A2B44]"
                />
                {label}
            </label>
            <ErrorText message={form.errors.can_write} />
        </div>
    );
}

function ErrorText({ message }) {
    return message ? <p className="mt-2 text-sm text-red-600">{message}</p> : null;
}
