import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const DEFAULT_FORM = {
    name: '',
    slug: '',
    description: '',
    is_admin: false,
    is_active: true,
    menu_keys: [],
};

export default function RolesIndex({ roles = [], menuRegistry = [] }) {
    const [editingRole, setEditingRole] = useState(null);
    const createForm = useForm({ ...DEFAULT_FORM });
    const editForm = useForm({ ...DEFAULT_FORM });

    const selectableMenu = menuRegistry.filter((item) => !item.admin_only);

    const startEdit = (role) => {
        setEditingRole(role);
        editForm.setData({
            name: role.name ?? '',
            slug: role.slug ?? '',
            description: role.description ?? '',
            is_admin: !!role.is_admin,
            is_active: !!role.is_active,
            menu_keys: role.menu_keys ?? [],
        });
        editForm.clearErrors();
    };

    const toggleMenuKey = (form, key) => {
        const current = form.data.menu_keys ?? [];
        form.setData(
            'menu_keys',
            current.includes(key) ? current.filter((k) => k !== key) : [...current, key],
        );
    };

    return (
        <AdminLayout title="Roles">
            <Head title="Roles" />

            <div className="space-y-6">
                <section className="app-widget p-4">
                    <h2 className="text-base font-semibold text-slate-900">Create role</h2>
                    <form
                        className="mt-4 space-y-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            createForm.post(route('roles.store'), {
                                preserveScroll: true,
                                onSuccess: () => createForm.setData({ ...DEFAULT_FORM, menu_keys: [] }),
                            });
                        }}
                    >
                        <RoleFields form={createForm} selectableMenu={selectableMenu} toggleMenuKey={toggleMenuKey} />
                        <div className="flex justify-end">
                            <button type="submit" className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                                Save role
                            </button>
                        </div>
                    </form>
                </section>

                <section className="app-widget overflow-x-auto p-4">
                    <h2 className="mb-4 text-base font-semibold text-slate-900">Existing roles</h2>
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                        <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-3 py-2 text-left">Name</th>
                                <th className="px-3 py-2 text-left">Slug</th>
                                <th className="px-3 py-2 text-left">Admin</th>
                                <th className="px-3 py-2 text-left">Active</th>
                                <th className="px-3 py-2 text-left">Users</th>
                                <th className="px-3 py-2 text-left">Menu items</th>
                                <th className="px-3 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {roles.map((role) => (
                                <tr key={role.id}>
                                    <td className="px-3 py-2 font-medium">{role.name}</td>
                                    <td className="px-3 py-2 text-slate-600">{role.slug}</td>
                                    <td className="px-3 py-2">{role.is_admin ? 'Yes' : 'No'}</td>
                                    <td className="px-3 py-2">{role.is_active ? 'Yes' : 'No'}</td>
                                    <td className="px-3 py-2">{role.users_count}</td>
                                    <td className="px-3 py-2 text-slate-600">{role.menu_keys?.length ?? 0}</td>
                                    <td className="px-3 py-2">
                                        <button
                                            type="button"
                                            onClick={() => startEdit(role)}
                                            className="rounded-lg border border-slate-300 px-3 py-1 text-xs font-medium hover:bg-slate-50"
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </section>
            </div>

            {editingRole && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4">
                    <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                        <h3 className="text-base font-semibold text-slate-900">Edit role: {editingRole.name}</h3>
                        <form
                            className="mt-4 space-y-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                editForm.patch(route('roles.update', editingRole.id), {
                                    preserveScroll: true,
                                    onSuccess: () => setEditingRole(null),
                                });
                            }}
                        >
                            <RoleFields
                                form={editForm}
                                selectableMenu={selectableMenu}
                                toggleMenuKey={toggleMenuKey}
                                isSystem={editingRole.is_system}
                            />
                            <div className="flex flex-wrap justify-between gap-2">
                                {!editingRole.is_system && editingRole.users_count === 0 && (
                                    <button
                                        type="button"
                                        onClick={() => {
                                            if (confirm('Delete this role?')) {
                                                router.delete(route('roles.destroy', editingRole.id), {
                                                    preserveScroll: true,
                                                    onSuccess: () => setEditingRole(null),
                                                });
                                            }
                                        }}
                                        className="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700"
                                    >
                                        Delete
                                    </button>
                                )}
                                <div className="ml-auto flex gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setEditingRole(null)}
                                        className="rounded-lg border border-slate-300 px-4 py-2 text-sm"
                                    >
                                        Cancel
                                    </button>
                                    <button type="submit" className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                                        Save changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}

function RoleFields({ form, selectableMenu, toggleMenuKey, isSystem = false }) {
    return (
        <>
            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <Field label="Name" value={form.data.name} onChange={(v) => form.setData('name', v)} error={form.errors.name} />
                <Field
                    label="Slug"
                    value={form.data.slug}
                    onChange={(v) => form.setData('slug', v)}
                    error={form.errors.slug}
                    disabled={isSystem}
                />
                <div className="md:col-span-2">
                    <label className="mb-1 block text-sm font-medium text-slate-600">Description</label>
                    <textarea
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                        rows={2}
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    />
                </div>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={!!form.data.is_admin}
                        disabled={isSystem}
                        onChange={(e) => form.setData('is_admin', e.target.checked)}
                    />
                    Full administrator (all menu items)
                </label>
                <label className="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={!!form.data.is_active}
                        disabled={isSystem && form.data.is_admin}
                        onChange={(e) => form.setData('is_active', e.target.checked)}
                    />
                    Active
                </label>
            </div>

            {!form.data.is_admin && (
                <div>
                    <p className="mb-2 text-sm font-medium text-slate-700">Allowed menu items</p>
                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        {selectableMenu.map((item) => (
                            <label key={item.menu_key} className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={(form.data.menu_keys ?? []).includes(item.menu_key)}
                                    onChange={() => toggleMenuKey(form, item.menu_key)}
                                />
                                {item.menu_key}
                            </label>
                        ))}
                    </div>
                    <ErrorText message={form.errors.menu_keys} />
                </div>
            )}
        </>
    );
}

function Field({ label, value, onChange, error, disabled = false, type = 'text' }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-slate-600">{label}</label>
            <input
                type={type}
                value={value}
                disabled={disabled}
                onChange={(e) => onChange(e.target.value)}
                className="block h-10 w-full rounded-lg border border-slate-300 px-3 text-sm disabled:bg-slate-100"
            />
            <ErrorText message={error} />
        </div>
    );
}

function ErrorText({ message }) {
    return message ? <p className="mt-1 text-sm text-red-600">{message}</p> : null;
}
