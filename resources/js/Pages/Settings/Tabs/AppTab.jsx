export default function AppTab({ t }) {
    return (
        <div className="app-widget p-4">
            <h2 className="text-base font-semibold text-slate-900">{t.appTitle}</h2>
            <p className="mt-2 text-sm text-slate-700">{t.appBody}</p>
        </div>
    );
}
