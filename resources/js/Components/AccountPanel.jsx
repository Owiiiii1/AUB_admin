import { useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function AccountPanel({
    t,
    account = null,
    linkableUsers = [],
    createUrl = null,
    linkUrl = null,
    unlinkUrl = null,
    disableUrl = null,
    canWrite = false,
    defaultName = '',
    defaultEmail = '',
    blockedMessage = null,
}) {
    const [mode, setMode] = useState(null);
    const createForm = useForm({
        name: defaultName,
        email: defaultEmail,
        password: '',
        is_active: true,
    });
    const linkForm = useForm({
        user_id: linkableUsers[0]?.id ?? '',
    });
    const actionForm = useForm({});

    const statusLabel = useMemo(() => {
        if (!account) {
            return t.accountNotCreated;
        }

        return account.is_active ? t.accountActive : t.accountInactive;
    }, [account, t]);

    if (blockedMessage) {
        return (
            <div className="rounded-md border border-[#E5E5E5] bg-[#FCFBF7] p-4">
                <h3 className="text-sm font-semibold uppercase tracking-[0.08em] text-[#04162e]">{t.accountTitle}</h3>
                <p className="mt-2 text-sm text-slate-600">{blockedMessage}</p>
            </div>
        );
    }

    return (
        <div className="rounded-md border border-[#E5E5E5] bg-[#FCFBF7] p-4">
            <h3 className="text-sm font-semibold uppercase tracking-[0.08em] text-[#04162e]">{t.accountTitle}</h3>
            {account ? (
                <div className="mt-2 space-y-1 text-sm text-slate-700">
                    <p>
                        <span className="font-medium">{t.accountLinked}:</span> {account.email}
                    </p>
                    <p>
                        <span className="font-medium">{t.fieldName}:</span> {account.name}
                    </p>
                    <p>
                        <span className="font-medium">{t.colStatus ?? t.fieldIsActive}:</span> {statusLabel}
                    </p>
                </div>
            ) : (
                <p className="mt-2 text-sm text-slate-600">{t.accountNotCreated}</p>
            )}

            {createForm.errors.account && <p className="mt-2 text-sm text-red-600">{createForm.errors.account}</p>}
            {linkForm.errors.user_id && <p className="mt-2 text-sm text-red-600">{linkForm.errors.user_id}</p>}
            {actionForm.errors.account && <p className="mt-2 text-sm text-red-600">{actionForm.errors.account}</p>}

            {canWrite && createUrl && !account && mode !== 'create' && mode !== 'link' && (
                <div className="mt-3 flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            createForm.setData({
                                name: defaultName,
                                email: defaultEmail,
                                password: '',
                                is_active: true,
                            });
                            setMode('create');
                        }}
                        className="rounded-md bg-[#1A2B44] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-white hover:bg-[#132033]"
                    >
                        {t.createAccount}
                    </button>
                    {linkUrl && (
                        <button
                            type="button"
                            onClick={() => setMode('link')}
                            className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 hover:bg-slate-50"
                        >
                            {t.linkAccount}
                        </button>
                    )}
                </div>
            )}

            {canWrite && account && (
                <div className="mt-3 flex flex-wrap gap-2">
                    {unlinkUrl && (
                        <button
                            type="button"
                            onClick={() => actionForm.post(unlinkUrl, { preserveScroll: true })}
                            disabled={actionForm.processing}
                            className="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 hover:bg-slate-50"
                        >
                            {t.unlinkAccount}
                        </button>
                    )}
                    {disableUrl && account.is_active && (
                        <button
                            type="button"
                            onClick={() => actionForm.post(disableUrl, { preserveScroll: true })}
                            disabled={actionForm.processing}
                            className="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-red-700 hover:bg-red-100"
                        >
                            {t.disableAccount}
                        </button>
                    )}
                </div>
            )}

            {mode === 'create' && createUrl && (
                <form
                    className="mt-4 grid grid-cols-1 gap-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        createForm.post(createUrl, {
                            preserveScroll: true,
                            onSuccess: () => setMode(null),
                        });
                    }}
                >
                    <AccountField form={createForm} field="name" label={t.fieldName} />
                    <AccountField form={createForm} field="email" label={t.fieldEmail} type="email" />
                    <AccountField form={createForm} field="password" label={t.fieldPassword} type="password" />
                    <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input
                            type="checkbox"
                            checked={createForm.data.is_active === true}
                            onChange={(e) => createForm.setData('is_active', e.target.checked)}
                        />
                        {t.fieldIsActive}
                    </label>
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={() => setMode(null)} className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700">
                            {t.cancel}
                        </button>
                        <button type="submit" disabled={createForm.processing} className="rounded-md bg-[#1A2B44] px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60">
                            {t.createAccount}
                        </button>
                    </div>
                </form>
            )}

            {mode === 'link' && linkUrl && (
                <form
                    className="mt-4 space-y-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        linkForm.post(linkUrl, {
                            preserveScroll: true,
                            onSuccess: () => setMode(null),
                        });
                    }}
                >
                    {linkableUsers.length === 0 ? (
                        <p className="text-sm text-slate-600">{t.noLinkableUsers}</p>
                    ) : (
                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-[#44474d]">{t.linkAccount}</label>
                            <select
                                value={linkForm.data.user_id}
                                onChange={(e) => linkForm.setData('user_id', e.target.value)}
                                className="h-10 w-full rounded-md border border-[#E5E5E5] bg-white px-3 text-sm"
                            >
                                {linkableUsers.map((user) => (
                                    <option key={user.id} value={user.id}>
                                        {user.name} ({user.email})
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={() => setMode(null)} className="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700">
                            {t.cancel}
                        </button>
                        <button type="submit" disabled={linkForm.processing || linkableUsers.length === 0} className="rounded-md bg-[#1A2B44] px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60">
                            {t.linkAccount}
                        </button>
                    </div>
                </form>
            )}
        </div>
    );
}

function AccountField({ form, field, label, type = 'text' }) {
    return (
        <div>
            <label className="mb-1 block text-xs font-semibold uppercase tracking-[0.08em] text-[#44474d]">{label}</label>
            <input
                type={type}
                value={form.data[field]}
                onChange={(e) => form.setData(field, e.target.value)}
                className="h-10 w-full rounded-md border border-[#E5E5E5] bg-white px-3 text-sm text-[#1b1c1c] focus:border-[#1A2B44] focus:outline-none"
            />
            {form.errors[field] && <p className="mt-1 text-xs text-red-600">{form.errors[field]}</p>}
        </div>
    );
}
