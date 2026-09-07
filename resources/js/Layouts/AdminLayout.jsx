import { Link, useForm, usePage } from '@inertiajs/react';
import {
    Archive,
    CalendarDays,
    ChartColumn,
    ChevronDown,
    Contact,
    FileText,
    Globe,
    Home,
    LogOut,
    Menu,
    MessageSquare,
    Scissors,
    School,
    Settings,
    Timer,
    UserCircle2,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';

const languageOptions = [
    { value: 'it', label: 'Italiano' },
    { value: 'uk', label: 'Українська' },
    { value: 'en', label: 'English' },
    { value: 'ru', label: 'Русский' },
];

const iconByMenuKey = {
    dashboard: Home,
    students: Contact,
    teachers: UserCircle2,
    settings: Settings,
    statistics: ChartColumn,
};

const labelKeyByMenuKey = {
    dashboard: 'home',
    students: 'students',
    teachers: 'teachers',
    settings: 'settings',
    statistics: 'statistics',
};

const BOTTOM_MENU_KEYS = ['settings', 'statistics'];
const PLACEHOLDER_MENU_ITEMS = [
    { key: 'coursesAndGroups', icon: School, routeName: 'courses-groups.index' },
    { key: 'scheduleService', icon: Timer, routeName: 'weekly-schedule.index' },
    { key: 'documents', icon: FileText, routeName: 'placeholder.documents' },
    { key: 'communication', icon: MessageSquare, routeName: 'placeholder.communication' },
    { key: 'events', icon: CalendarDays, routeName: 'placeholder.events' },
    { key: 'costumeService', icon: Scissors, routeName: 'placeholder.costume-service' },
];
const PLACEHOLDER_ARCHIVE_ITEM = { key: 'archive', icon: Archive, routeName: 'placeholder.archive' };

export default function AdminLayout({ title, headerCenter = null, children }) {
    const { auth, locale = 'it', owlAdmin = {}, adminMenu: adminMenuProp } = usePage().props;
    const adminMenu = Array.isArray(adminMenuProp) ? adminMenuProp : [];
    const user = auth?.user;
    const ai = owlAdmin?.ai ?? {};
    const aiConnected = !!ai.connected;
    const aiBadgeText = ai?.status_label
        ?? (aiConnected
            ? `AI: connected — ${ai.provider_label ?? ai.provider ?? 'Unknown'} / ${ai.model ?? 'unknown'}`
            : 'AI: not connected');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [statisticsOpen, setStatisticsOpen] = useState(() => {
        try {
            return typeof route === 'function' ? route().current('statistics.*') : false;
        } catch {
            return false;
        }
    });
    const localeForm = useForm({ locale });

    const changeLocale = (nextLocale) => {
        if (nextLocale === locale || localeForm.processing) {
            return;
        }

        localeForm.setData('locale', nextLocale);
        localeForm.post(route('settings.language.update'), {
            preserveScroll: true,
        });
    };

    const uiText = {
        it: {
            home: 'Home',
            students: 'Studenti',
            teachers: 'Insegnanti',
            settings: 'Impostazioni',
            statistics: 'Statistiche',
            logs: 'Log',
            profile: 'Profilo',
            logout: 'Esci',
            adminPanel: 'Pannello amministratore',
            coursesAndGroups: 'Corsi e gruppi',
            lessons: 'Lezioni',
            scheduleService: 'Servizio orari',
            documents: 'Documenti',
            communication: 'Comunicazioni',
            events: 'Eventi',
            archive: 'Archivio',
            costumeService: 'Servizio costumi',
        },
        en: {
            home: 'Home',
            students: 'Students',
            teachers: 'Teachers',
            settings: 'Settings',
            statistics: 'Statistics',
            logs: 'Logs',
            profile: 'Profile',
            logout: 'Logout',
            adminPanel: 'Admin Panel',
            coursesAndGroups: 'Courses and groups',
            lessons: 'Lessons',
            scheduleService: 'Schedule service',
            documents: 'Documents',
            communication: 'Communication',
            events: 'Events',
            archive: 'Archive',
            costumeService: 'Costume service',
        },
        ru: {
            home: 'Главная',
            students: 'Студенты',
            teachers: 'Преподаватели',
            settings: 'Настройки',
            statistics: 'Статистика',
            logs: 'Логи',
            profile: 'Профиль',
            logout: 'Выход',
            adminPanel: 'Панель администратора',
            coursesAndGroups: 'Курсы и группы',
            lessons: 'Уроки',
            scheduleService: 'Сервис расписаний',
            documents: 'Документы',
            communication: 'Коммуникация',
            events: 'События',
            archive: 'Архив',
            costumeService: 'Сервис костюмов',
        },
        uk: {
            home: 'Головна',
            students: 'Студенти',
            teachers: 'Викладачі',
            settings: 'Налаштування',
            statistics: 'Статистика',
            logs: 'Логи',
            profile: 'Профіль',
            logout: 'Вихід',
            adminPanel: 'Панель адміністратора',
            coursesAndGroups: 'Курси і групи',
            lessons: 'Уроки',
            scheduleService: 'Сервіс розкладів',
            documents: 'Документи',
            communication: 'Комунікація',
            events: 'Події',
            archive: 'Архів',
            costumeService: 'Сервіс костюмів',
        },
    };
    const t = uiText[locale] ?? uiText.it;
    const currentLanguageLabel = languageOptions.find((option) => option.value === locale)?.label ?? 'Italiano';

    const mainMenuItems = adminMenu.filter((item) => !BOTTOM_MENU_KEYS.includes(item.menu_key));
    const bottomMenuItems = BOTTOM_MENU_KEYS
        .map((key) => adminMenu.find((item) => item.menu_key === key))
        .filter(Boolean);

    const navLinkClass = (active) =>
        `flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
            active
                ? 'border-l-2 border-white bg-white/10 text-white'
                : 'text-slate-300 hover:bg-white/5 hover:text-white'
        }`;

    const isRouteActive = (pattern) => {
        try {
            return typeof route === 'function' ? route().current(pattern) : false;
        } catch {
            return false;
        }
    };

    const renderMenuItem = (item, mobile = false) => {
        const Icon = iconByMenuKey[item.menu_key] ?? Home;
        const labelKey = labelKeyByMenuKey[item.menu_key] ?? item.menu_key;
        const routeName = item.route_name;
        const routePrefix = routeName?.split('.')[0] ?? routeName;
        const itemKey = `${mobile ? 'mobile-' : ''}${item.menu_key}`;

        if (item.has_children && item.child_route_name) {
            return (
                <div key={itemKey}>
                    <button
                        type="button"
                        onClick={() => setStatisticsOpen((open) => !open)}
                        className={`${navLinkClass(isRouteActive('statistics.*'))} w-full`}
                    >
                        <Icon className="h-4 w-4" />
                        {t[labelKey] ?? item.menu_key}
                        <ChevronDown className={`ml-auto h-4 w-4 transition ${statisticsOpen ? 'rotate-180' : ''}`} />
                    </button>
                    {statisticsOpen && (
                        <Link
                            href={route(item.child_route_name)}
                            className={`${navLinkClass(isRouteActive('statistics.logs'))} ml-5 mt-1`}
                            onClick={() => mobile && setMobileMenuOpen(false)}
                        >
                            <FileText className="h-4 w-4" />
                            {t.logs}
                        </Link>
                    )}
                </div>
            );
        }

        if (!routeName) {
            return null;
        }

        return (
            <Link
                key={itemKey}
                href={route(routeName)}
                className={navLinkClass(isRouteActive(`${routePrefix}*`) || isRouteActive(routeName))}
                onClick={() => mobile && setMobileMenuOpen(false)}
            >
                <Icon className="h-4 w-4" />
                {t[labelKey] ?? item.menu_key}
            </Link>
        );
    };

    const renderSidebarNav = (mobile = false) => (
        <>
            <div className="space-y-1.5">
                {mainMenuItems.map((item) => renderMenuItem(item, mobile))}
                {PLACEHOLDER_MENU_ITEMS.map((item) => {
                    const Icon = item.icon;
                    const active = isRouteActive(item.routeName);
                    return (
                        <div key={`${mobile ? 'mobile-' : ''}placeholder-${item.key}`}>
                            {item.key === 'costumeService' && <div className="my-2 border-t border-white/10" />}
                            <Link
                                href={route(item.routeName)}
                                className={`${navLinkClass(active)} w-full opacity-90`}
                                onClick={() => mobile && setMobileMenuOpen(false)}
                            >
                                <Icon className="h-4 w-4" />
                                {t[item.key]}
                            </Link>
                        </div>
                    );
                })}
                <div className="mt-3 border-t border-white/10 pt-3">
                    <Link
                        href={route(PLACEHOLDER_ARCHIVE_ITEM.routeName)}
                        key={`${mobile ? 'mobile-' : ''}placeholder-${PLACEHOLDER_ARCHIVE_ITEM.key}`}
                        className={`${navLinkClass(isRouteActive(PLACEHOLDER_ARCHIVE_ITEM.routeName))} w-full opacity-90`}
                        onClick={() => mobile && setMobileMenuOpen(false)}
                    >
                        <PLACEHOLDER_ARCHIVE_ITEM.icon className="h-4 w-4" />
                        {t[PLACEHOLDER_ARCHIVE_ITEM.key]}
                    </Link>
                </div>
            </div>
            {bottomMenuItems.length > 0 && (
                <div className="mt-auto border-t border-white/10 pt-4">
                    <div className="space-y-1.5">
                        {bottomMenuItems.map((item) => renderMenuItem(item, mobile))}
                    </div>
                </div>
            )}
        </>
    );

    return (
        <div className="h-screen overflow-hidden bg-slate-50 text-slate-900">
            <div className="flex h-screen">
                <aside className="hidden w-72 flex-col bg-[#1A2B44] text-white shadow-2xl lg:flex lg:flex-col">
                    <div className="border-b border-white/10 px-6 py-5">
                        <div className="flex flex-col items-center gap-2 text-center">
                            <img src="/images/logo-aub-white.png" alt="AUB" className="h-32 w-auto object-contain" />
                            <p className="text-xs uppercase tracking-wide text-slate-300">{t.adminPanel}</p>
                        </div>
                    </div>

                    <nav className="flex min-h-0 flex-1 flex-col px-4 pb-6 pt-4">
                        {renderSidebarNav()}
                    </nav>
                </aside>

                <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                    <SheetContent side="left" className="w-80 border-r-0 bg-[#1A2B44] p-0 text-white">
                        <SheetHeader className="border-b border-white/10 px-6 py-5 text-center">
                            <div className="flex flex-col items-center gap-2">
                                <img src="/images/logo-aub-white.png" alt="AUB" className="h-32 w-auto object-contain" />
                                <SheetTitle className="text-xs font-medium uppercase tracking-wide text-slate-300">{t.adminPanel}</SheetTitle>
                            </div>
                        </SheetHeader>
                        <nav className="flex min-h-0 flex-1 flex-col px-4 pb-6 pt-4">
                            {renderSidebarNav(true)}
                        </nav>
                    </SheetContent>
                </Sheet>

                <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
                    <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur-xl">
                        <div className="relative flex h-16 items-center justify-between px-4 sm:px-8">
                            <div className="flex min-w-0 items-center gap-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="h-9 w-9 lg:hidden"
                                    onClick={() => setMobileMenuOpen(true)}
                                >
                                    <Menu className="h-4 w-4" />
                                </Button>
                                <h1 className="truncate text-base font-semibold text-slate-900">{title}</h1>
                            </div>
                            {headerCenter && (
                                <div className="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <div className="pointer-events-auto">
                                        {headerCenter}
                                    </div>
                                </div>
                            )}
                            <div className="flex items-center gap-3">
                                <span
                                    className={`hidden max-w-[320px] truncate rounded-full px-3 py-1 text-xs font-semibold md:inline-flex ${
                                        aiConnected
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-red-100 text-red-700'
                                    }`}
                                    title={aiBadgeText}
                                >
                                    {aiBadgeText}
                                </span>

                                <div className="relative">
                                    <div className="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 pr-9 text-sm font-medium text-slate-700 shadow-sm">
                                        <Globe className="h-4 w-4 text-slate-500" />
                                        <span>{currentLanguageLabel}</span>
                                        <ChevronDown className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                                    </div>
                                    <select
                                        value={locale}
                                        onChange={(e) => changeLocale(e.target.value)}
                                        disabled={localeForm.processing}
                                        className="absolute inset-0 h-10 w-full cursor-pointer opacity-0"
                                        aria-label="Language"
                                    >
                                        {languageOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {user && (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <button
                                                type="button"
                                                className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-left shadow-sm transition hover:bg-slate-50"
                                            >
                                                <div className="hidden min-w-0 sm:block">
                                                    <p className="max-w-40 truncate text-sm font-medium text-slate-800">{user.name}</p>
                                                    <p className="max-w-40 truncate text-xs text-slate-500">{user.email}</p>
                                                </div>
                                                <UserCircle2 className="h-8 w-8 shrink-0 text-slate-500 sm:hidden" />
                                                <ChevronDown className="h-4 w-4 shrink-0 text-slate-400" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-52">
                                            <div className="px-2 py-1.5 sm:hidden">
                                                <p className="truncate text-sm font-medium text-slate-900">{user.name}</p>
                                                <p className="truncate text-xs text-slate-500">{user.email}</p>
                                            </div>
                                            <DropdownMenuSeparator className="sm:hidden" />
                                            <DropdownMenuItem asChild>
                                                <Link href={route('profile.edit')} className="cursor-pointer">
                                                    <UserCircle2 className="h-4 w-4" />
                                                    {t.profile}
                                                </Link>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem asChild>
                                                <Link
                                                    href={route('logout')}
                                                    method="post"
                                                    as="button"
                                                    className="w-full cursor-pointer"
                                                >
                                                    <LogOut className="h-4 w-4" />
                                                    {t.logout}
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                )}
                            </div>
                        </div>
                    </header>

                    <main className="flex-1 overflow-y-auto p-4 sm:p-8">
                        <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            {children}
                        </div>
                    </main>
                </div>
            </div>
        </div>
    );
}
