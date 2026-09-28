import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, usePage } from '@inertiajs/react';

const TEXT = {
    it: {
        title: 'Richieste di eliminazione',
        empty: 'Nessuna richiesta.',
        verify: 'Segna verificata',
        reject: 'Rifiuta',
        complete: 'Completa ed esegui',
        confirm: 'Confermo l’esecuzione. L’accesso verrà chiuso secondo le regole del ruolo.',
        note: 'Nota',
        remove: 'Da rimuovere',
        retain: 'Da conservare',
    },
    en: {
        title: 'Deletion requests',
        empty: 'No requests.',
        verify: 'Mark verified',
        reject: 'Reject',
        complete: 'Complete and apply',
        confirm: 'I confirm execution. Access will be closed under the rules for that role.',
        note: 'Note',
        remove: 'To remove',
        retain: 'To retain',
    },
    ru: {
        title: 'Запросы на удаление',
        empty: 'Запросов нет.',
        verify: 'Отметить проверенным',
        reject: 'Отклонить',
        complete: 'Завершить и выполнить',
        confirm: 'Подтверждаю выполнение. Доступ будет закрыт по правилам этой роли.',
        note: 'Заметка',
        remove: 'Удалить',
        retain: 'Оставить',
    },
    uk: {
        title: 'Запити на видалення',
        empty: 'Запитів немає.',
        verify: 'Позначити перевіреним',
        reject: 'Відхилити',
        complete: 'Завершити і виконати',
        confirm: 'Підтверджую виконання. Доступ буде закрито за правилами цієї ролі.',
        note: 'Нотатка',
        remove: 'Видалити',
        retain: 'Залишити',
    },
};

export default function DeletionRequests({ requests }) {
    const locale = usePage().props?.locale || 'it';
    const t = TEXT[locale] || TEXT.it;

    return (
        <AdminLayout title={t.title}>
            <Head title={t.title} />
            {requests.length === 0 ? <p>{t.empty}</p> : null}
            <div className="space-y-4">
                {requests.map((row) => (
                    <article key={row.id} className="rounded-xl border border-slate-200 bg-white p-4">
                        <p className="font-medium">{row.email}</p>
                        <p className="text-sm text-slate-600">
                            {row.claimed_role}
                            {row.linked_account_type ? ` · ${row.linked_account_type}` : ''}
                            {` · ${row.source} · ${row.status}`}
                        </p>
                        <p className="text-sm text-slate-500">{row.requested_at}</p>
                        {row.message ? <p className="mt-2 text-sm">{row.message}</p> : null}
                        <div className="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <h2 className="text-sm font-semibold">{t.remove}</h2>
                                <ul className="list-disc pl-5 text-sm">
                                    {row.remove.map((item) => <li key={item.key}>{item.detail}</li>)}
                                </ul>
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold">{t.retain}</h2>
                                <ul className="list-disc pl-5 text-sm">
                                    {row.retain.map((item) => <li key={item.key}>{item.detail}</li>)}
                                </ul>
                            </div>
                        </div>
                        {row.open ? (
                            <div className="mt-3 flex flex-wrap gap-2">
                                <button type="button" className="rounded border px-3 py-1 text-sm" onClick={() => router.post(`/settings/privacy/${row.id}/verify`)}>{t.verify}</button>
                                <form className="flex flex-wrap items-center gap-2" onSubmit={(event) => {
                                    event.preventDefault();
                                    const data = new FormData(event.currentTarget);
                                    router.post(`/settings/privacy/${row.id}/reject`, { resolution_note: data.get('resolution_note') });
                                }}>
                                    <input name="resolution_note" placeholder={t.note} className="rounded border px-2 py-1 text-sm" />
                                    <button type="submit" className="rounded border px-3 py-1 text-sm">{t.reject}</button>
                                </form>
                                <form className="flex flex-wrap items-center gap-2" onSubmit={(event) => {
                                    event.preventDefault();
                                    const data = new FormData(event.currentTarget);
                                    router.post(`/settings/privacy/${row.id}/complete`, {
                                        confirm: data.get('confirm') ? 1 : 0,
                                        resolution_note: data.get('resolution_note'),
                                    });
                                }}>
                                    <label className="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="confirm" value="1" />
                                        {t.confirm}
                                    </label>
                                    <input name="resolution_note" placeholder={t.note} className="rounded border px-2 py-1 text-sm" />
                                    <button type="submit" className="rounded bg-rose-800 px-3 py-1 text-sm text-white">{t.complete}</button>
                                </form>
                            </div>
                        ) : null}
                    </article>
                ))}
            </div>
        </AdminLayout>
    );
}
