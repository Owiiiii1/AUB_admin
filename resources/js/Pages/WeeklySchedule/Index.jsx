import AdminLayout from '@/Layouts/AdminLayout';
import LessonPalette from '@/Pages/WeeklySchedule/LessonPalette';
import TimelineLessonBlock from '@/Pages/WeeklySchedule/TimelineLessonBlock';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    ChevronLeft,
    ChevronRight,
    Copy,
    FileDown,
    Plus,
    SlidersHorizontal,
    Sparkles,
    Trash2,
    BookOpen,
    History,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

const TEXT = {
    it: {
        pageTitle: 'Servizio orari',
        weekLabel: 'Settimana',
        viewGeneral: 'Generale',
        viewGroup: 'Gruppo',
        selectGroupTitle: 'Seleziona gruppo',
        selectGroupHint: 'Scegli il gruppo per vedere l\'orario settimanale.',
        changeGroup: 'Cambia gruppo',
        noGroupSelected: 'Nessun gruppo selezionato.',
        groupScheduleEmpty: 'Nessuna lezione per questo gruppo in settimana.',
        prevWeek: 'Settimana precedente',
        nextWeek: 'Settimana successiva',
        copyPrevious: 'Copia settimana precedente',
        weekSettings: 'Impostazioni settimana',
        weekSettingsTitle: 'Impostazioni settimana',
        checkConflicts: 'Controlla conflitti',
        publish: 'Pubblica orario',
        exportPdf: 'Esporta PDF',
        exportPdfSoon: 'Presto disponibile',
        unscheduledTitle: 'Gruppi non programmati',
        unscheduledEmpty: 'Tutti i gruppi hanno almeno una lezione programmata.',
        addLesson: 'Aggiungi lezione',
        editLesson: 'Modifica lezione',
        inspectorTitle: 'Dettagli lezione',
        noSelection: 'Seleziona una lezione nella griglia.',
        group: 'Gruppo',
        subject: 'Materia',
        teacher: 'Insegnante',
        course: 'Corso',
        discipline: 'Disciplina',
        weeklyTotal: 'Totale settimanale',
        scheduledTotal: 'Programmato',
        remainingTotal: 'Residuo',
        sessionDuration: 'Durata sessione',
        day: 'Giorno',
        building: 'Sede',
        room: 'Sala',
        start: 'Inizio',
        end: 'Fine',
        duration: 'Durata',
        status: 'Stato',
        notes: 'Note',
        edit: 'Modifica',
        duplicate: 'Duplica',
        remove: 'Rimuovi',
        cancel: 'Annulla',
        save: 'Salva',
        publishConfirmTitle: 'Pubblicare l\'orario?',
        publishConfirmText: 'L\'orario sarà visibile come pubblicato. I conflitti bloccano la pubblicazione.',
        copyConfirmTitle: 'Copiare la settimana precedente?',
        copyConfirmText: 'La settimana corrente contiene già {count} lezioni. Sostituirle?',
        copyConfirm: 'Copia e sostituisci',
        conflictsTitle: 'Conflitti rilevati',
        conflictsEmpty: 'Nessun conflitto.',
        statusDraft: 'Bozza',
        statusPublished: 'Pubblicato',
        statusLocked: 'Bloccato',
        minutes: 'min',
        clickToAdd: 'Clicca per aggiungere',
        move: 'Sposta',
        notAssigned: '—',
        halls: 'Sale',
        workingHours: 'Orario di lavoro',
        minWorkTime: 'Orario minimo',
        maxWorkTime: 'Orario massimo',
        dayHeaderColors: 'Colori intestazione giorni',
        resetDayColors: 'Reset colori giorni',
        selectAll: 'Seleziona tutto',
        clearAll: 'Deseleziona tutto',
        noHallsVisible: 'Nessuna sala selezionata. Apri le impostazioni settimana.',
        paletteGroupFilter: 'Gruppo',
        paletteFilterAll: 'Tutti',
        paletteFilterUnassigned: 'Non programmati',
        paletteCardsCount: 'lezioni',
        paletteEmpty: 'Nessuna lezione per questo filtro.',
        paletteDragHint: 'Trascina sulla griglia',
        paletteHoursShort: 'h',
        paletteLeftShort: 'h libere',
        dropLessonTitle: 'Inserisci lezione in orario',
        dropHoursLabel: 'Minuti da programmare',
        dropHoursHint: 'Disponibili {remaining} min su {weekly} min settimanali',
        dropTeacherLabel: 'Insegnante',
        dropPlacementLabel: 'Posizione',
        placeLesson: 'Inserisci',
        blockSettings: 'Impostazioni lezione',
        blockResizeStart: 'Modifica ora di inizio',
        blockMinutesLabel: 'Durata (minuti)',
        blockMinutesHint: 'Disponibili {remaining} min su {weekly} min settimanali',
        aiAssistant: 'Assistente IA',
        aiModalTitle: 'Assistente IA per la distribuzione',
        aiStatusConnected: 'Connesso',
        aiStatusDisconnected: 'Non connesso',
        aiModeEdit: 'Modifica',
        aiModeCreate: 'Crea nuovo',
        aiModeEditHint: 'Le lezioni già in orario restano fisse; si distribuiscono solo le ore residue.',
        aiModeCreateHint: 'L\'orario attuale verrà cancellato prima di creare quello nuovo.',
        aiPromptLabel: 'Prompt',
        aiPromptPlaceholder: 'Es.: distribuire tutte le lezioni TAM al mattino, evitare il sabato pomeriggio...',
        aiPromptHistory: 'Cronologia richieste',
        aiPromptHistoryEmpty: 'Nessuna richiesta salvata.',
        aiStart: 'Avvia',
        aiConfirmTitle: 'Avviare la distribuzione IA?',
        aiConfirmTextEdit: 'La rete neurale distribuirà le lezioni residue rispettando l\'orario già presente.',
        aiConfirmTextCreate: 'L\'orario attuale verrà eliminato e sostituito con una nuova distribuzione IA.',
        aiPartialTitle: 'Capacità insufficiente',
        aiPartialConfirm: 'Procedi con distribuzione parziale',
        aiReportTitle: 'Report distribuzione IA',
        aiRecommendationsTitle: 'Raccomandazioni IA',
        aiApplyRecommendations: 'Applica raccomandazioni e ridistribuisci',
        aiRecommendationsApplyPrefix: 'Applica obbligatoriamente queste raccomandazioni:',
        aiClose: 'Chiudi',
        aiProcessing: 'Distribuzione in corso...',
        aiClearingTimeline: 'Pulizia timeline in corso...',
        aiNotConnectedHint: 'Collega un provider IA nelle impostazioni per usare questa funzione.',
        aiInstructions: 'Istruzioni',
        aiInstructionsTitle: 'Cosa capisce l\'assistente',
        aiInstructionsIntro: 'L\'IA non mette le lezioni una per una: legge il prompt e lo traduce in regole. Poi un pianificatore automatico le applica sulla griglia. Scrivi in linguaggio naturale — queste sono le leve che funzionano davvero.',
        aiInstructionsLevers: [
            {
                title: 'Durata delle lezioni',
                text: 'Es.: «lezioni di 2 ore», «preferibilmente blocchi da 120 minuti». Regola fissa: due lezioni uguali da 1 ora di fila (stesso gruppo/materia/docente) diventano un blocco da 2 ore. I resti da 1 ora restano da 1 ora.',
            },
            {
                title: 'Pausa tra le lezioni',
                text: 'Es.: «pausa di 10 minuti». Il pianificatore usa scatti da 5 minuti (la timeline resta a 30).',
            },
            {
                title: 'Massimo ore al giorno per classe',
                text: 'Es.: «massimo 6 ore al giorno per classe».',
            },
            {
                title: 'Lezioni compatte (senza buchi)',
                text: 'Es.: «niente buchi lunghi», «finestre al massimo 15 minuti».',
            },
            {
                title: 'Mattina o pomeriggio (finestra del corso)',
                text: 'Lo shift è sul corso: finestra didattica 08:00–13:00 o 13:00–18:00. I corsi sono bilanciati tra le due fasce. Si modifica in Corsi e gruppi → impostazioni corso.',
            },
            {
                title: 'Un solo edificio al giorno',
                text: 'Es.: «una classe nello stesso edificio per giornata» (si può cambiare aula, non sede).',
            },
            {
                title: 'Finestra oraria preferita',
                text: 'Es.: «preferibilmente tra le 9 e le 13», «evitare il pomeriggio».',
            },
            {
                title: 'Carico insegnanti',
                text: 'Limite giornaliero predefinito: 8 ore. La frase «non considerare il carico docenti» non è ancora una leva affidabile.',
            },
        ],
        aiInstructionsNote: 'Restano sempre attivi: niente sovrapposizioni di aula/insegnante/gruppo, solo lun–ven; due lezioni uguali da 1 ora consecutive → un blocco da 2 ore. La timeline resta visualmente a 30 minuti; il pianificatore colloca a scatti da 5. Ciò che non è in questa lista spesso viene solo «capito» nel testo, ma non eseguito.',
        clearTimeline: 'Svuota timeline',
        clearTimelineConfirmTitle: 'Svuotare la timeline?',
        clearTimelineConfirmText: 'Tutte le lezioni della settimana corrente verranno rimosse.',
        clearTimelineConfirm: 'Svuota',
    },
    en: {
        pageTitle: 'Schedule service',
        weekLabel: 'Week',
        viewGeneral: 'General',
        viewGroup: 'Group',
        selectGroupTitle: 'Select group',
        selectGroupHint: 'Choose a group to see its weekly schedule.',
        changeGroup: 'Change group',
        noGroupSelected: 'No group selected.',
        groupScheduleEmpty: 'No lessons for this group this week.',
        prevWeek: 'Previous week',
        nextWeek: 'Next week',
        copyPrevious: 'Copy previous week',
        weekSettings: 'Week settings',
        weekSettingsTitle: 'Week settings',
        checkConflicts: 'Check conflicts',
        publish: 'Publish schedule',
        exportPdf: 'Export PDF',
        exportPdfSoon: 'Coming soon',
        unscheduledTitle: 'Unscheduled groups',
        unscheduledEmpty: 'All groups have at least one scheduled lesson.',
        addLesson: 'Add lesson',
        editLesson: 'Edit lesson',
        inspectorTitle: 'Lesson details',
        noSelection: 'Select a lesson in the grid.',
        group: 'Group',
        subject: 'Subject',
        teacher: 'Teacher',
        course: 'Course',
        discipline: 'Discipline',
        weeklyTotal: 'Weekly total',
        scheduledTotal: 'Scheduled',
        remainingTotal: 'Remaining',
        sessionDuration: 'Session duration',
        day: 'Day',
        building: 'Building',
        room: 'Room',
        start: 'Start',
        end: 'End',
        duration: 'Duration',
        status: 'Status',
        notes: 'Notes',
        edit: 'Edit',
        duplicate: 'Duplicate',
        remove: 'Remove',
        cancel: 'Cancel',
        save: 'Save',
        publishConfirmTitle: 'Publish schedule?',
        publishConfirmText: 'The schedule will be marked as published. Conflicts block publishing.',
        copyConfirmTitle: 'Copy previous week?',
        copyConfirmText: 'The current week already has {count} lessons. Replace them?',
        copyConfirm: 'Copy and replace',
        conflictsTitle: 'Detected conflicts',
        conflictsEmpty: 'No conflicts.',
        statusDraft: 'Draft',
        statusPublished: 'Published',
        statusLocked: 'Locked',
        minutes: 'min',
        clickToAdd: 'Click to add',
        move: 'Move',
        notAssigned: '—',
        halls: 'Halls',
        workingHours: 'Working hours',
        minWorkTime: 'Earliest time',
        maxWorkTime: 'Latest time',
        dayHeaderColors: 'Day header colors',
        resetDayColors: 'Reset day colors',
        selectAll: 'Select all',
        clearAll: 'Clear all',
        noHallsVisible: 'No halls selected. Open week settings.',
        paletteGroupFilter: 'Group',
        paletteFilterAll: 'All',
        paletteFilterUnassigned: 'Unassigned',
        paletteCardsCount: 'lessons',
        paletteEmpty: 'No lessons for this filter.',
        paletteDragHint: 'Drag onto the grid',
        paletteHoursShort: 'h',
        paletteLeftShort: 'h left',
        dropLessonTitle: 'Place lesson on schedule',
        dropHoursLabel: 'Minutes to schedule',
        dropHoursHint: '{remaining} min available of {weekly} weekly min',
        dropTeacherLabel: 'Teacher',
        dropPlacementLabel: 'Placement',
        placeLesson: 'Place',
        blockSettings: 'Lesson settings',
        blockResizeStart: 'Adjust start time',
        blockMinutesLabel: 'Duration (minutes)',
        blockMinutesHint: '{remaining} min available of {weekly} weekly min',
        aiAssistant: 'AI assistant',
        aiModalTitle: 'AI scheduling assistant',
        aiStatusConnected: 'Connected',
        aiStatusDisconnected: 'Not connected',
        aiModeEdit: 'Edit',
        aiModeCreate: 'Create new',
        aiModeEditHint: 'Existing scheduled lessons stay fixed; only remaining hours are placed.',
        aiModeCreateHint: 'The current timeline will be cleared before creating a new AI schedule.',
        aiPromptLabel: 'Prompt',
        aiPromptPlaceholder: 'E.g. place all TAM lessons in the morning, avoid Saturday afternoon...',
        aiPromptHistory: 'Request history',
        aiPromptHistoryEmpty: 'No saved requests yet.',
        aiStart: 'Start',
        aiConfirmTitle: 'Run AI scheduling?',
        aiConfirmTextEdit: 'The AI will distribute remaining lessons while keeping the current schedule intact.',
        aiConfirmTextCreate: 'The current timeline will be deleted and replaced with a new AI-generated schedule.',
        aiPartialTitle: 'Insufficient capacity',
        aiPartialConfirm: 'Proceed with partial scheduling',
        aiReportTitle: 'AI scheduling report',
        aiRecommendationsTitle: 'AI recommendations',
        aiApplyRecommendations: 'Apply recommendations and redistribute',
        aiRecommendationsApplyPrefix: 'Mandatory recommendations to apply:',
        aiClose: 'Close',
        aiProcessing: 'Scheduling in progress...',
        aiClearingTimeline: 'Clearing timeline...',
        aiNotConnectedHint: 'Connect an AI provider in settings to use this feature.',
        aiInstructions: 'Instructions',
        aiInstructionsTitle: 'What the assistant understands',
        aiInstructionsIntro: 'The AI does not place lessons one by one: it reads your prompt and turns it into rules. Then an automatic planner applies them on the grid. Write in natural language — these are the levers that actually work.',
        aiInstructionsLevers: [
            {
                title: 'Lesson length',
                text: 'E.g. “2-hour lessons”, “preferably 120-minute blocks”. Hard rule: two identical consecutive 1-hour lessons (same group/lesson/teacher) become one 2-hour block. A leftover single hour stays 1 hour.',
            },
            {
                title: 'Break between lessons',
                text: 'E.g. “10-minute breaks”. The planner uses 5-minute steps (the timeline view stays at 30).',
            },
            {
                title: 'Max hours per class per day',
                text: 'E.g. “maximum 6 hours per class per day”.',
            },
            {
                title: 'Compact schedule (no long gaps)',
                text: 'E.g. “no large gaps”, “windows at most 15 minutes”.',
            },
            {
                title: 'Morning or afternoon (course study window)',
                text: 'The shift is set on the course: study window 08:00–13:00 or 13:00–18:00. Courses are balanced across both bands. Edit in Courses & groups → course settings.',
            },
            {
                title: 'One building per day',
                text: 'E.g. “a class stays in one building for the day” (rooms can change, location cannot).',
            },
            {
                title: 'Preferred time window',
                text: 'E.g. “preferably between 9 and 13”, “avoid afternoons”.',
            },
            {
                title: 'Teacher load',
                text: 'Default daily limit: 8 hours. “Ignore teacher load” is not a reliable lever yet.',
            },
        ],
        aiInstructionsNote: 'Always enforced: no room/teacher/group overlaps, Mon–Fri only; two identical consecutive 1-hour lessons → one 2-hour block. The timeline still looks like 30-minute rows; the planner places on a 5-minute grid. Anything outside this list may be “understood” in text but not executed.',
        clearTimeline: 'Clear timeline',
        clearTimelineConfirmTitle: 'Clear the timeline?',
        clearTimelineConfirmText: 'All lessons on the current week will be removed.',
        clearTimelineConfirm: 'Clear',
    },
    ru: {
        pageTitle: 'Сервис расписаний',
        weekLabel: 'Неделя',
        viewGeneral: 'Общее',
        viewGroup: 'Группа',
        selectGroupTitle: 'Выбор учебной группы',
        selectGroupHint: 'Выберите группу, чтобы увидеть её расписание на неделю.',
        changeGroup: 'Сменить группу',
        noGroupSelected: 'Группа не выбрана.',
        groupScheduleEmpty: 'У этой группы нет занятий на неделе.',
        prevWeek: 'Предыдущая неделя',
        nextWeek: 'Следующая неделя',
        copyPrevious: 'Копировать прошлую неделю',
        weekSettings: 'Настройки недели',
        weekSettingsTitle: 'Настройки недели',
        checkConflicts: 'Проверить конфликты',
        publish: 'Опубликовать расписание',
        exportPdf: 'Экспорт PDF',
        exportPdfSoon: 'Скоро',
        unscheduledTitle: 'Незапланированные группы',
        unscheduledEmpty: 'У всех групп есть хотя бы одно занятие в расписании.',
        addLesson: 'Добавить занятие',
        editLesson: 'Редактировать занятие',
        inspectorTitle: 'Детали занятия',
        noSelection: 'Выберите занятие в сетке.',
        group: 'Группа',
        subject: 'Предмет',
        teacher: 'Преподаватель',
        course: 'Курс',
        discipline: 'Дисциплина',
        weeklyTotal: 'Всего в неделю',
        scheduledTotal: 'Распределено',
        remainingTotal: 'Осталось',
        sessionDuration: 'Длительность занятия',
        day: 'День',
        building: 'Здание',
        room: 'Зал',
        start: 'Начало',
        end: 'Конец',
        duration: 'Длительность',
        status: 'Статус',
        notes: 'Заметки',
        edit: 'Редактировать',
        duplicate: 'Дублировать',
        remove: 'Удалить',
        cancel: 'Отмена',
        save: 'Сохранить',
        publishConfirmTitle: 'Опубликовать расписание?',
        publishConfirmText: 'Расписание будет помечено как опубликованное. Конфликты блокируют публикацию.',
        copyConfirmTitle: 'Копировать прошлую неделю?',
        copyConfirmText: 'В текущей неделе уже {count} занятий. Заменить их?',
        copyConfirm: 'Копировать и заменить',
        conflictsTitle: 'Обнаруженные конфликты',
        conflictsEmpty: 'Конфликтов нет.',
        statusDraft: 'Черновик',
        statusPublished: 'Опубликовано',
        statusLocked: 'Заблокировано',
        minutes: 'мин',
        clickToAdd: 'Нажмите, чтобы добавить',
        move: 'Переместить',
        notAssigned: '—',
        halls: 'Залы',
        workingHours: 'Рабочее время',
        minWorkTime: 'Минимальное время',
        maxWorkTime: 'Максимальное время',
        dayHeaderColors: 'Цвета шапки дней',
        resetDayColors: 'Сбросить цвета дней',
        selectAll: 'Выбрать все',
        clearAll: 'Снять выбор',
        noHallsVisible: 'Не выбрано ни одного зала. Откройте настройки недели.',
        paletteGroupFilter: 'Группа',
        paletteFilterAll: 'Все',
        paletteFilterUnassigned: 'Нераспределённые',
        paletteCardsCount: 'уроков',
        paletteEmpty: 'Нет уроков для этого фильтра.',
        paletteDragHint: 'Перетащите на сетку',
        paletteHoursShort: 'ч',
        paletteLeftShort: 'ост.',
        dropLessonTitle: 'Поставить урок в расписание',
        dropHoursLabel: 'Сколько минут поставить',
        dropHoursHint: 'Доступно {remaining} мин из {weekly} мин в неделю',
        dropTeacherLabel: 'Преподаватель',
        dropPlacementLabel: 'Позиция',
        placeLesson: 'Поставить',
        blockSettings: 'Настройки занятия',
        blockResizeStart: 'Изменить время начала',
        blockMinutesLabel: 'Длительность (минуты)',
        blockMinutesHint: 'Доступно {remaining} мин из {weekly} мин в неделю',
        aiAssistant: 'ИИ-помощник',
        aiModalTitle: 'ИИ-помощник распределения',
        aiStatusConnected: 'Подключена',
        aiStatusDisconnected: 'Не подключена',
        aiModeEdit: 'Правка',
        aiModeCreate: 'Создать новое',
        aiModeEditHint: 'Уже распределённые занятия не трогаем — ставим только остаток часов.',
        aiModeCreateHint: 'Текущее расписание будет удалено перед созданием нового.',
        aiPromptLabel: 'Промпт',
        aiPromptPlaceholder: 'Например: все уроки TAM поставить утром, избегать субботы после обеда...',
        aiPromptHistory: 'История запросов',
        aiPromptHistoryEmpty: 'Сохранённых запросов пока нет.',
        aiStart: 'Начать',
        aiConfirmTitle: 'Запустить ИИ-распределение?',
        aiConfirmTextEdit: 'Нейросеть распределит оставшиеся уроки, не меняя уже стоящие на таймлайне.',
        aiConfirmTextCreate: 'Текущий таймлайн будет удалён и заменён новым распределением от ИИ.',
        aiPartialTitle: 'Недостаточно мест',
        aiPartialConfirm: 'Распределить тех, кто поместится',
        aiReportTitle: 'Отчёт ИИ-распределения',
        aiRecommendationsTitle: 'Рекомендации ИИ',
        aiApplyRecommendations: 'Учесть рекомендации и распределить снова',
        aiRecommendationsApplyPrefix: 'Обязательно учти эти рекомендации:',
        aiClose: 'Закрыть',
        aiProcessing: 'Идёт распределение...',
        aiClearingTimeline: 'Очистка таймлайна...',
        aiNotConnectedHint: 'Подключите провайдера ИИ в настройках, чтобы использовать эту функцию.',
        aiInstructions: 'Инструкция',
        aiInstructionsTitle: 'Что понимает помощник',
        aiInstructionsIntro: 'ИИ не расставляет уроки вручную: он читает промпт и превращает его в правила. Дальше автоматический планировщик применяет их на сетке. Пишите обычным языком — ниже рычаги, которые реально работают.',
        aiInstructionsLevers: [
            {
                title: 'Длительность уроков',
                text: 'Напр.: «уроки по 2 часа», «преимущественно блоки по 120 минут». Жёсткое правило: два одинаковых урока по 1 часу подряд (та же группа/предмет/преподаватель) объединяются в один блок на 2 часа. Одиночный хвост в 1 час остаётся часовым.',
            },
            {
                title: 'Перерыв между уроками',
                text: 'Напр.: «перерывы по 10 минут». Планировщик ставит с шагом 5 минут (внешний вид таймлайна остаётся 30).',
            },
            {
                title: 'Максимум часов в день на класс',
                text: 'Напр.: «на один класс максимум 6 часов в день».',
            },
            {
                title: 'Компактное расписание (без окон)',
                text: 'Напр.: «уроки подряд», «окна не больше 15 минут».',
            },
            {
                title: 'Утро или после обеда (учебное окно курса)',
                text: 'Смена задаётся на курсе: учебное окно 08:00–13:00 или 13:00–18:00. Курсы делятся примерно поровну между сменами. Меняется в «Курсы и группы» → настройки курса.',
            },
            {
                title: 'Одно здание в день',
                text: 'Напр.: «класс в один день в одном здании» (залы можно менять, локацию — нет).',
            },
            {
                title: 'Предпочтительное окно времени',
                text: 'Напр.: «желательно с 9 до 13», «избегать после обеда».',
            },
            {
                title: 'Нагрузка преподавателей',
                text: 'Дневной лимит по умолчанию: 8 часов. Фраза «не учитывай нагрузку преподавателей» пока не надёжный рычаг.',
            },
        ],
        aiInstructionsNote: 'Всегда действуют: нет пересечений зал/преподаватель/группа, только пн–пт; два одинаковых урока по 1 ч подряд → один блок на 2 ч. Таймлайн визуально как раньше (30 мин), планировщик ставит с шагом 5 мин. Всё, чего нет в этом списке, часто «понимается» в тексте, но не выполняется.',
        clearTimeline: 'Очистить таймлайн',
        clearTimelineConfirmTitle: 'Очистить таймлайн?',
        clearTimelineConfirmText: 'Все занятия текущей недели будут удалены.',
        clearTimelineConfirm: 'Очистить',
    },
    uk: {
        pageTitle: 'Сервіс розкладів',
        weekLabel: 'Тиждень',
        viewGeneral: 'Загальне',
        viewGroup: 'Група',
        selectGroupTitle: 'Вибір навчальної групи',
        selectGroupHint: 'Оберіть групу, щоб побачити її розклад на тиждень.',
        changeGroup: 'Змінити групу',
        noGroupSelected: 'Групу не вибрано.',
        groupScheduleEmpty: 'У цієї групи немає занять на тижні.',
        prevWeek: 'Попередній тиждень',
        nextWeek: 'Наступний тиждень',
        copyPrevious: 'Копіювати минулий тиждень',
        weekSettings: 'Налаштування тижня',
        weekSettingsTitle: 'Налаштування тижня',
        checkConflicts: 'Перевірити конфлікти',
        publish: 'Опублікувати розклад',
        exportPdf: 'Експорт PDF',
        exportPdfSoon: 'Незабаром',
        unscheduledTitle: 'Незаплановані групи',
        unscheduledEmpty: 'У всіх груп є хоча б одне заняття в розкладі.',
        addLesson: 'Додати заняття',
        editLesson: 'Редагувати заняття',
        inspectorTitle: 'Деталі заняття',
        noSelection: 'Оберіть заняття в сітці.',
        group: 'Група',
        subject: 'Предмет',
        teacher: 'Викладач',
        course: 'Курс',
        discipline: 'Дисципліна',
        weeklyTotal: 'Всього на тиждень',
        scheduledTotal: 'Розподілено',
        remainingTotal: 'Залишилось',
        sessionDuration: 'Тривалість заняття',
        day: 'День',
        building: 'Будівля',
        room: 'Зал',
        start: 'Початок',
        end: 'Кінець',
        duration: 'Тривалість',
        status: 'Статус',
        notes: 'Нотатки',
        edit: 'Редагувати',
        duplicate: 'Дублювати',
        remove: 'Видалити',
        cancel: 'Скасувати',
        save: 'Зберегти',
        publishConfirmTitle: 'Опублікувати розклад?',
        publishConfirmText: 'Розклад буде позначено як опублікований. Конфлікти блокують публікацію.',
        copyConfirmTitle: 'Копіювати минулий тиждень?',
        copyConfirmText: 'У поточному тижні вже {count} занять. Замінити їх?',
        copyConfirm: 'Копіювати і замінити',
        conflictsTitle: 'Виявлені конфлікти',
        conflictsEmpty: 'Конфліктів немає.',
        statusDraft: 'Чернетка',
        statusPublished: 'Опубліковано',
        statusLocked: 'Заблоковано',
        minutes: 'хв',
        clickToAdd: 'Натисніть, щоб додати',
        move: 'Перемістити',
        notAssigned: '—',
        halls: 'Зали',
        workingHours: 'Робочий час',
        minWorkTime: 'Мінімальний час',
        maxWorkTime: 'Максимальний час',
        dayHeaderColors: 'Кольори шапки днів',
        resetDayColors: 'Скинути кольори днів',
        selectAll: 'Вибрати всі',
        clearAll: 'Зняти вибір',
        noHallsVisible: 'Не вибрано жодного залу. Відкрийте налаштування тижня.',
        paletteGroupFilter: 'Група',
        paletteFilterAll: 'Усі',
        paletteFilterUnassigned: 'Нерозподілені',
        paletteCardsCount: 'уроків',
        paletteEmpty: 'Немає уроків для цього фільтра.',
        paletteDragHint: 'Перетягніть на сітку',
        paletteHoursShort: 'год',
        paletteLeftShort: 'залиш.',
        dropLessonTitle: 'Поставити урок у розклад',
        dropHoursLabel: 'Скільки хвилин поставити',
        dropHoursHint: 'Доступно {remaining} хв з {weekly} хв на тиждень',
        dropTeacherLabel: 'Викладач',
        dropPlacementLabel: 'Позиція',
        placeLesson: 'Поставити',
        blockSettings: 'Налаштування заняття',
        blockResizeStart: 'Змінити час початку',
        blockMinutesLabel: 'Тривалість (хвилини)',
        blockMinutesHint: 'Доступно {remaining} хв з {weekly} хв на тиждень',
        aiAssistant: 'ШІ-помічник',
        aiModalTitle: 'ШІ-помічник розподілу',
        aiStatusConnected: 'Підключено',
        aiStatusDisconnected: 'Не підключено',
        aiModeEdit: 'Правка',
        aiModeCreate: 'Створити нове',
        aiModeEditHint: 'Уже розставлені заняття не змінюємо — ставимо лише залишок годин.',
        aiModeCreateHint: 'Поточний розклад буде видалено перед створенням нового.',
        aiPromptLabel: 'Промпт',
        aiPromptPlaceholder: 'Наприклад: всі уроки TAM поставити вранці, уникати суботи після обіду...',
        aiPromptHistory: 'Історія запитів',
        aiPromptHistoryEmpty: 'Збережених запитів поки немає.',
        aiStart: 'Почати',
        aiConfirmTitle: 'Запустити ШІ-розподіл?',
        aiConfirmTextEdit: 'Нейромережа розподілить залишкові заняття, не змінюючи те, що вже на таймлайні.',
        aiConfirmTextCreate: 'Поточний таймлайн буде видалено і замінено новим розподілом від ШІ.',
        aiPartialTitle: 'Недостатньо місць',
        aiPartialConfirm: 'Розподілити тих, хто поміститься',
        aiReportTitle: 'Звіт ШІ-розподілу',
        aiRecommendationsTitle: 'Рекомендації ШІ',
        aiApplyRecommendations: 'Врахувати рекомендації та розподілити знову',
        aiRecommendationsApplyPrefix: 'Обовʼязково врахуй ці рекомендації:',
        aiClose: 'Закрити',
        aiProcessing: 'Йде розподіл...',
        aiClearingTimeline: 'Очищення таймлайну...',
        aiNotConnectedHint: 'Підключіть провайдера ШІ в налаштуваннях, щоб використовувати цю функцію.',
        aiInstructions: 'Інструкція',
        aiInstructionsTitle: 'Що розуміє помічник',
        aiInstructionsIntro: 'ШІ не розставляє уроки вручну: він читає промпт і перетворює його на правила. Далі автоматичний планувальник застосовує їх на сітці. Пишіть звичайною мовою — нижче важелі, які реально працюють.',
        aiInstructionsLevers: [
            {
                title: 'Тривалість уроків',
                text: 'Напр.: «уроки по 2 години», «переважно блоки по 120 хвилин». Жорстке правило: два однакові уроки по 1 годині поспіль (та сама група/предмет/викладач) об’єднуються в один блок на 2 години. Одиночний хвіст в 1 годину лишається годинним.',
            },
            {
                title: 'Перерва між уроками',
                text: 'Напр.: «перерви по 10 хвилин». Планувальник ставить з кроком 5 хвилин (вигляд таймлайну лишається 30).',
            },
            {
                title: 'Максимум годин на день на клас',
                text: 'Напр.: «на один клас максимум 6 годин на день».',
            },
            {
                title: 'Компактний розклад (без вікон)',
                text: 'Напр.: «уроки підряд», «вікна не більше 15 хвилин».',
            },
            {
                title: 'Ранок або після обіду (навчальне вікно курсу)',
                text: 'Зміну задають на курсі: навчальне вікно 08:00–13:00 або 13:00–18:00. Курси діляться приблизно порівну між змінами. Змінюється в «Курси і групи» → налаштування курсу.',
            },
            {
                title: 'Одна будівля на день',
                text: 'Напр.: «клас в один день в одній будівлі» (зали можна міняти, локацію — ні).',
            },
            {
                title: 'Бажане вікно часу',
                text: 'Напр.: «бажано з 9 до 13», «уникати після обіду».',
            },
            {
                title: 'Навантаження викладачів',
                text: 'Денний ліміт за замовчуванням: 8 годин. Фраза «не враховуй навантаження викладачів» поки не надійний важіль.',
            },
        ],
        aiInstructionsNote: 'Завжди діють: немає перетинів зал/викладач/група, лише пн–пт; два однакові уроки по 1 год поспіль → один блок на 2 год. Таймлайн візуально як раніше (30 хв), планувальник ставить з кроком 5 хв. Усе, чого немає в цьому списку, часто «розуміється» в тексті, але не виконується.',
        clearTimeline: 'Очистити таймлайн',
        clearTimelineConfirmTitle: 'Очистити таймлайн?',
        clearTimelineConfirmText: 'Усі заняття поточного тижня будуть видалені.',
        clearTimelineConfirm: 'Очистити',
    },
};

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

function buildWorkTimeOptions(stepMinutes = 30) {
    const options = [];
    for (let minutes = 0; minutes <= (23 * 60) + 30; minutes += stepMinutes) {
        options.push(minutesToTime(minutes));
    }

    return options;
}

const WORK_TIME_OPTIONS = buildWorkTimeOptions(30);

function addMinutesToTime(time, minutes) {
    return minutesToTime(timeToMinutes(time) + Number(minutes));
}

function hasTimelineConflict(candidate, existing) {
    if (candidate.lesson_date !== existing.lesson_date) {
        return false;
    }

    const candidateStart = timeToMinutes(candidate.starts_at);
    const candidateEnd = timeToMinutes(candidate.ends_at);
    const existingStart = timeToMinutes(existing.starts_at);
    const existingEnd = timeToMinutes(existing.ends_at);
    const intersects = candidateStart < existingEnd && candidateEnd > existingStart;

    if (!intersects) {
        return false;
    }

    return (
        (candidate.course_group_id && existing.course_group_id && Number(candidate.course_group_id) === Number(existing.course_group_id))
        || (candidate.teacher_id && existing.teacher_id && Number(candidate.teacher_id) === Number(existing.teacher_id))
        || (candidate.academy_room_id && existing.academy_room_id && Number(candidate.academy_room_id) === Number(existing.academy_room_id))
    );
}

function resolveNextFreeStart({
    lessonDate,
    startsAt,
    durationMinutes,
    courseGroupId,
    teacherId,
    roomId,
    scheduledLessons,
    gridStart,
    gridSpan,
}) {
    const gridStartMinutes = timeToMinutes(gridStart);
    const gridEndMinutes = gridStartMinutes + gridSpan;
    const safeDuration = Math.max(30, Math.round(Number(durationMinutes) / 30) * 30);
    let cursor = Math.max(gridStartMinutes, timeToMinutes(startsAt));

    for (let i = 0; i < 200; i += 1) {
        const candidateEnd = cursor + safeDuration;
        if (candidateEnd > gridEndMinutes) {
            return null;
        }

        const candidate = {
            lesson_date: lessonDate,
            starts_at: minutesToTime(cursor),
            ends_at: minutesToTime(candidateEnd),
            course_group_id: courseGroupId,
            teacher_id: teacherId,
            academy_room_id: roomId,
        };

        const overlapping = scheduledLessons
            .filter((lesson) => hasTimelineConflict(candidate, lesson))
            .sort((a, b) => timeToMinutes(a.ends_at) - timeToMinutes(b.ends_at));

        if (overlapping.length === 0) {
            return candidate.starts_at;
        }

        cursor = Math.round(timeToMinutes(overlapping[overlapping.length - 1].ends_at) / 30) * 30;
    }

    return null;
}

function snapTimeFromDrop(clientY, cellElement, gridStart, gridSpan) {
    const rect = cellElement.getBoundingClientRect();
    const offsetY = Math.max(0, Math.min(clientY - rect.top, rect.height));
    const ratio = rect.height > 0 ? offsetY / rect.height : 0;
    const rawMinutes = timeToMinutes(gridStart) + (ratio * gridSpan);
    const snapped = Math.round(rawMinutes / 30) * 30;
    const maxMinutes = timeToMinutes(gridStart) + gridSpan - 30;

    return minutesToTime(Math.max(timeToMinutes(gridStart), Math.min(snapped, maxMinutes)));
}

function blockStyleVertical(startsAt, endsAt, gridStart, gridSpan) {
    const startMinutes = timeToMinutes(gridStart);
    const top = ((timeToMinutes(startsAt) - startMinutes) / gridSpan) * 100;
    const height = ((timeToMinutes(endsAt) - timeToMinutes(startsAt)) / gridSpan) * 100;
    return {
        top: `${Math.max(0, top)}%`,
        height: `${Math.max(2, height)}%`,
    };
}

const SLOT_HEIGHT_PX = 26;

function formatWeekRange(start, end, locale) {
    const startDate = new Date(`${start}T12:00:00`);
    const endDate = new Date(`${end}T12:00:00`);
    const fmt = new Intl.DateTimeFormat(locale === 'it' ? 'it-IT' : locale === 'ru' ? 'ru-RU' : locale === 'uk' ? 'uk-UA' : 'en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
    return `${fmt.format(startDate)} — ${fmt.format(endDate)}`;
}

function Modal({ title, children, onClose, wide = false, elevated = false }) {
    const content = (
        <div className={`fixed inset-0 flex items-center justify-center bg-slate-900/40 p-4 ${elevated ? 'z-[80]' : 'z-50'}`}>
            <div className={`w-full ${wide ? 'max-w-xl' : 'max-w-md'} max-h-[90vh] overflow-y-auto rounded-xl border border-slate-200 bg-white p-5 shadow-xl`}>
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-base font-semibold text-slate-900">{title}</h3>
                    <button type="button" onClick={onClose} className="rounded-lg p-1 text-slate-500 hover:bg-slate-100">
                        <X className="h-4 w-4" />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );

    if (typeof document === 'undefined') {
        return content;
    }

    return createPortal(content, document.body);
}

function ToolbarIconButton({
    onClick,
    disabled = false,
    title,
    variant = 'default',
    badge,
    children,
}) {
    const wrapperRef = useRef(null);
    const [tooltipVisible, setTooltipVisible] = useState(false);
    const [tooltipPosition, setTooltipPosition] = useState({ top: 0, left: 0 });

    const variants = {
        default: 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        amber: 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100',
        red: 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
        dark: 'border-[#1A2B44] bg-[#1A2B44] text-white hover:bg-[#132033]',
        muted: 'border-slate-300 bg-white text-slate-500 hover:bg-slate-50',
    };

    const updateTooltipPosition = () => {
        if (!wrapperRef.current) {
            return;
        }

        const rect = wrapperRef.current.getBoundingClientRect();
        setTooltipPosition({
            top: rect.bottom + 6,
            left: rect.left + (rect.width / 2),
        });
    };

    const showTooltip = () => {
        if (!title) {
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

    return (
        <>
            <span
                ref={wrapperRef}
                className="relative inline-flex"
                onMouseEnter={showTooltip}
                onMouseLeave={hideTooltip}
            >
                <button
                    type="button"
                    onClick={onClick}
                    disabled={disabled}
                    aria-label={title}
                    className={`relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border transition disabled:cursor-not-allowed disabled:opacity-50 ${variants[variant] ?? variants.default}`}
                >
                    {children}
                    {badge != null && badge > 0 && (
                        <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-600 px-1 text-[10px] font-semibold text-white">
                            {badge}
                        </span>
                    )}
                </button>
            </span>

            {tooltipVisible && title && typeof document !== 'undefined' && createPortal(
                <div
                    className="pointer-events-none fixed z-[10001] max-w-[240px] -translate-x-1/2 whitespace-normal rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-center text-xs font-medium text-slate-800 shadow-lg"
                    style={{
                        top: tooltipPosition.top,
                        left: tooltipPosition.left,
                    }}
                >
                    {title}
                </div>,
                document.body,
            )}
        </>
    );
}

function TimelineLessonBlockWrapper({
    lesson,
    weekId,
    gridStart,
    gridSpan,
    gridHeight,
    canEdit,
    draggable,
    onDragStart,
    t,
}) {
    const positionStyle = blockStyleVertical(lesson.starts_at, lesson.ends_at, gridStart, gridSpan);
    const appearance = lessonBlockAppearance(lesson);

    return (
        <TimelineLessonBlock
            lesson={lesson}
            weekId={weekId}
            positionStyle={positionStyle}
            appearance={appearance}
            gridStart={gridStart}
            gridSpan={gridSpan}
            gridHeight={gridHeight}
            canEdit={canEdit}
            draggable={draggable}
            onDragStart={onDragStart}
            t={t}
        />
    );
}

const TIME_COLUMN_WIDTH = 72;
const ROOM_COLUMN_MIN_WIDTH = 96;
const GROUP_DAY_COLUMN_MIN_WIDTH = 160;
const DEFAULT_DAY_HEADER_COLOR = '#bdf171';
const SCHEDULE_VIEW_STORAGE_KEY = 'weekly-schedule-view-mode';
const SELECTED_GROUP_STORAGE_KEY = 'weekly-schedule-selected-group';
const AI_PROMPT_HISTORY_KEY = 'weekly-schedule-ai-prompt-history';
const AI_PROMPT_HISTORY_LIMIT = 5;

function readAiPromptHistory() {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(AI_PROMPT_HISTORY_KEY);
        const parsed = JSON.parse(raw);

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .filter((item) => typeof item === 'string' && item.trim() !== '')
            .slice(0, AI_PROMPT_HISTORY_LIMIT);
    } catch {
        return [];
    }
}

function appendAiPromptHistory(prompt) {
    const trimmed = prompt.trim();

    if (trimmed === '' || typeof window === 'undefined') {
        return readAiPromptHistory();
    }

    const next = [trimmed, ...readAiPromptHistory().filter((item) => item !== trimmed)]
        .slice(0, AI_PROMPT_HISTORY_LIMIT);

    window.localStorage.setItem(AI_PROMPT_HISTORY_KEY, JSON.stringify(next));

    return next;
}

function resolveDefaultPaletteFilter(cards) {
    for (const card of cards) {
        if (Number(card.remaining_hours) > 0) {
            return String(card.course_group_id);
        }
    }

    return 'all';
}

function parseHexColor(hexColor) {
    const normalized = hexColor?.replace('#', '') ?? '';
    if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return null;
    }

    return {
        r: parseInt(normalized.slice(0, 2), 16),
        g: parseInt(normalized.slice(2, 4), 16),
        b: parseInt(normalized.slice(4, 6), 16),
    };
}

function colorBrightness({ r, g, b }) {
    return ((r * 299) + (g * 587) + (b * 114)) / 1000;
}

/** White text only on strongly dark fills; otherwise black. */
function getContrastColor(hexColor) {
    const rgb = parseHexColor(hexColor);
    if (!rgb) {
        return '#000000';
    }

    return colorBrightness(rgb) < 85 ? '#ffffff' : '#000000';
}

function blendHexWithWhite(hexColor, alpha) {
    const rgb = parseHexColor(hexColor);
    if (!rgb) {
        return null;
    }

    const mix = (channel) => Math.round((channel * alpha) + (255 * (1 - alpha)));
    const toHex = (value) => value.toString(16).padStart(2, '0');

    return `#${toHex(mix(rgb.r))}${toHex(mix(rgb.g))}${toHex(mix(rgb.b))}`;
}

function hexToRgba(hexColor, alpha) {
    const rgb = parseHexColor(hexColor);
    if (!rgb) {
        return null;
    }

    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
}

function lessonBlockAppearance(lesson) {
    if (lesson.status === 'cancelled') {
        return {
            className: 'border-slate-400 bg-slate-100 text-black/50 line-through opacity-70',
            style: {},
        };
    }

    if (lesson.has_conflict) {
        return {
            className: 'border-red-500 bg-red-50 text-black',
            style: {},
        };
    }

    if (lesson.group_color) {
        const fillAlpha = 0.22;
        const fillHex = blendHexWithWhite(lesson.group_color, fillAlpha) ?? '#ffffff';

        return {
            className: 'border-2 shadow-sm',
            style: {
                backgroundColor: hexToRgba(lesson.group_color, fillAlpha) ?? undefined,
                borderColor: lesson.group_color,
                color: getContrastColor(fillHex),
            },
        };
    }

    if (lesson.status === 'published') {
        return {
            className: 'border-emerald-500 bg-emerald-50 text-black',
            style: {},
        };
    }

    return {
        className: 'border-indigo-400 bg-indigo-50 text-black',
        style: {},
    };
}

export default function WeeklyScheduleIndex({
    week,
    buildings = [],
    timeSlots = [],
    grid = {},
    days = [],
    scheduledLessons = [],
    groups = [],
    groupLessonCards = [],
    teachers = [],
    subjects = [],
    conflicts = [],
    canExportPdf = false,
}) {
    const { locale = 'it', auth, errors, owlAdmin } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;
    const canWrite = auth?.user?.can_write === true;
    const aiStatus = owlAdmin?.ai ?? {};
    const aiConnected = aiStatus.connected === true;
    const gridStart = grid.start ?? '08:00';
    const gridSpan = grid.span_minutes ?? 870;
    const gridHeight = timeSlots.length * SLOT_HEIGHT_PX;

    const [showLessonModal, setShowLessonModal] = useState(false);
    const [editingLesson, setEditingLesson] = useState(null);
    const [showPublishModal, setShowPublishModal] = useState(false);
    const [showCopyModal, setShowCopyModal] = useState(false);
    const [showConflictsModal, setShowConflictsModal] = useState(false);
    const [showWeekSettingsModal, setShowWeekSettingsModal] = useState(false);
    const [visibleRoomIds, setVisibleRoomIds] = useState([]);
    const [dayHeaderColors, setDayHeaderColors] = useState({});
    const [workStartsAt, setWorkStartsAt] = useState(week.work_starts_at ?? grid.start ?? '08:00');
    const [workEndsAt, setWorkEndsAt] = useState(week.work_ends_at ?? grid.end ?? '22:30');
    const [savingWeekSettings, setSavingWeekSettings] = useState(false);
    const [scheduleView, setScheduleView] = useState(() => {
        if (typeof window === 'undefined') {
            return 'general';
        }
        const stored = window.localStorage.getItem(SCHEDULE_VIEW_STORAGE_KEY);
        return stored === 'group' ? 'group' : 'general';
    });
    const [selectedGroupId, setSelectedGroupId] = useState(() => {
        if (typeof window === 'undefined') {
            return null;
        }
        const stored = Number(window.localStorage.getItem(SELECTED_GROUP_STORAGE_KEY));
        return Number.isFinite(stored) && stored > 0 ? stored : null;
    });
    const [paletteFilter, setPaletteFilter] = useState(() => resolveDefaultPaletteFilter(groupLessonCards));
    const [dragOverCellKey, setDragOverCellKey] = useState(null);
    const [dropPlacement, setDropPlacement] = useState(null);
    const [dropHours, setDropHours] = useState('1');
    const [dropTeacherId, setDropTeacherId] = useState('');
    const [dropProcessing, setDropProcessing] = useState(false);
    const [showAiModal, setShowAiModal] = useState(false);
    const [showAiConfirmModal, setShowAiConfirmModal] = useState(false);
    const [showAiPartialModal, setShowAiPartialModal] = useState(false);
    const [showAiReportModal, setShowAiReportModal] = useState(false);
    const [showAiInstructions, setShowAiInstructions] = useState(false);
    const [showClearTimelineModal, setShowClearTimelineModal] = useState(false);
    const [aiMode, setAiMode] = useState('edit');
    const [aiPrompt, setAiPrompt] = useState('');
    const [showAiHistory, setShowAiHistory] = useState(false);
    const [aiPromptHistory, setAiPromptHistory] = useState(() => readAiPromptHistory());
    const [aiProcessing, setAiProcessing] = useState(false);
    const [aiProcessingPhase, setAiProcessingPhase] = useState(null);
    const [aiPartialMessage, setAiPartialMessage] = useState('');
    const [aiReport, setAiReport] = useState('');
    const [aiRecommendations, setAiRecommendations] = useState([]);
    const [aiWarnings, setAiWarnings] = useState([]);
    const [aiError, setAiError] = useState('');
    const aiPendingRequestRef = useRef({ prompt: '', mode: 'edit' });

    useEffect(() => {
        if (showAiModal) {
            setAiPromptHistory(readAiPromptHistory());
        }
    }, [showAiModal]);

    const rememberAiPrompt = (prompt) => {
        if (!prompt.trim()) {
            return;
        }

        setAiPromptHistory(appendAiPromptHistory(prompt));
    };

    useEffect(() => {
        setPaletteFilter(resolveDefaultPaletteFilter(groupLessonCards));
    }, [week.id]);

    const lessonForm = useForm({
        lesson_date: days[0]?.date ?? week.week_start_date,
        starts_at: '09:00',
        ends_at: '10:00',
        academy_building_id: buildings[0]?.id ?? '',
        academy_room_id: buildings[0]?.rooms?.[0]?.id ?? '',
        course_group_id: '',
        teacher_id: '',
        lesson_id: '',
        title: '',
        notes: '',
        color: '',
        status: 'scheduled',
    });

    const roomsForBuilding = useMemo(() => {
        const building = buildings.find((item) => String(item.id) === String(lessonForm.data.academy_building_id));
        return building?.rooms ?? [];
    }, [buildings, lessonForm.data.academy_building_id]);

    const lessonsByRoomDate = useMemo(() => {
        const map = {};
        scheduledLessons.forEach((lesson) => {
            const key = `${lesson.lesson_date}:${lesson.academy_room_id}`;
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(lesson);
        });
        return map;
    }, [scheduledLessons]);

    const lessonsByGroupDate = useMemo(() => {
        const map = {};
        scheduledLessons.forEach((lesson) => {
            if (!lesson.course_group_id) {
                return;
            }
            const key = `${lesson.lesson_date}:${lesson.course_group_id}`;
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(lesson);
        });
        return map;
    }, [scheduledLessons]);

    const selectedGroup = useMemo(
        () => groups.find((group) => Number(group.id) === Number(selectedGroupId)) ?? null,
        [groups, selectedGroupId],
    );

    const selectedGroupLessons = useMemo(() => {
        if (!selectedGroupId) {
            return [];
        }

        return scheduledLessons.filter(
            (lesson) => Number(lesson.course_group_id) === Number(selectedGroupId),
        );
    }, [scheduledLessons, selectedGroupId]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }
        window.localStorage.setItem(SCHEDULE_VIEW_STORAGE_KEY, scheduleView);
    }, [scheduleView]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }
        if (selectedGroupId) {
            window.localStorage.setItem(SELECTED_GROUP_STORAGE_KEY, String(selectedGroupId));
        }
    }, [selectedGroupId]);

    useEffect(() => {
        if (!selectedGroupId) {
            return;
        }
        if (!groups.some((group) => Number(group.id) === Number(selectedGroupId))) {
            setSelectedGroupId(null);
        }
    }, [groups, selectedGroupId]);

    const scheduleColumns = useMemo(() => {
        const columns = [];
        days.forEach((day) => {
            buildings.forEach((building) => {
                (building.rooms ?? []).forEach((room) => {
                    columns.push({
                        key: `${day.date}-${building.id}-${room.id}`,
                        dayDate: day.date,
                        dayLabel: day.label,
                        buildingId: building.id,
                        buildingName: building.name,
                        roomId: room.id,
                        roomName: room.name,
                    });
                });
            });
        });
        return columns;
    }, [days, buildings]);

    const roomOptions = useMemo(() => (
        buildings.flatMap((building) =>
            (building.rooms ?? []).map((room) => ({
                id: room.id,
                name: room.name,
                building_name: building.name,
            })),
        )
    ), [buildings]);

    useEffect(() => {
        const allRoomIds = roomOptions.map((room) => room.id);
        const storageKey = `weekly-schedule-visible-rooms:${week.id}`;

        let nextRoomIds = allRoomIds;
        if (typeof window !== 'undefined') {
            try {
                const raw = window.localStorage.getItem(storageKey);
                if (raw !== null) {
                    const parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) {
                        nextRoomIds = parsed
                            .map((id) => Number(id))
                            .filter((id) => allRoomIds.includes(id));
                    }
                }
            } catch {
                nextRoomIds = allRoomIds;
            }
        }

        setVisibleRoomIds(nextRoomIds);
    }, [week.id, roomOptions]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }
        const storageKey = `weekly-schedule-visible-rooms:${week.id}`;
        window.localStorage.setItem(storageKey, JSON.stringify(visibleRoomIds));
    }, [week.id, visibleRoomIds]);

    useEffect(() => {
        const storageKey = `weekly-schedule-day-colors:${week.id}`;
        const defaults = Object.fromEntries(days.map((day) => [day.date, DEFAULT_DAY_HEADER_COLOR]));

        let next = defaults;
        if (typeof window !== 'undefined') {
            try {
                const raw = window.localStorage.getItem(storageKey);
                if (raw !== null) {
                    const parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        next = {
                            ...defaults,
                            ...Object.fromEntries(
                                Object.entries(parsed).filter(
                                    ([key, value]) => days.some((d) => d.date === key) && typeof value === 'string',
                                ),
                            ),
                        };
                    }
                }
            } catch {
                next = defaults;
            }
        }

        setDayHeaderColors(next);
    }, [week.id, days]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }
        const storageKey = `weekly-schedule-day-colors:${week.id}`;
        window.localStorage.setItem(storageKey, JSON.stringify(dayHeaderColors));
    }, [week.id, dayHeaderColors]);

    useEffect(() => {
        setWorkStartsAt(week.work_starts_at ?? grid.start ?? '08:00');
        setWorkEndsAt(week.work_ends_at ?? grid.end ?? '22:30');
    }, [week.id, week.work_starts_at, week.work_ends_at, grid.start, grid.end]);

    const saveWeekSettings = () => {
        if (!canWrite || !week.is_editable) {
            setShowWeekSettingsModal(false);
            return;
        }

        setSavingWeekSettings(true);
        router.patch(route('weekly-schedule.settings.update', week.id), {
            work_starts_at: workStartsAt,
            work_ends_at: workEndsAt,
        }, {
            preserveScroll: true,
            onFinish: () => setSavingWeekSettings(false),
            onSuccess: () => setShowWeekSettingsModal(false),
        });
    };

    const visibleScheduleColumns = useMemo(() => (
        scheduleColumns.filter((column) => visibleRoomIds.includes(column.roomId))
    ), [scheduleColumns, visibleRoomIds]);

    const dayColumnSpans = useMemo(
        () => days.map((day) => ({
            date: day.date,
            label: day.label,
            span: visibleScheduleColumns.filter((col) => col.dayDate === day.date).length,
        })).filter((day) => day.span > 0),
        [days, visibleScheduleColumns],
    );

    const buildingColumnSpans = useMemo(() => {
        const spans = [];
        days.forEach((day) => {
            buildings.forEach((building) => {
                const span = visibleScheduleColumns.filter(
                    (column) => column.dayDate === day.date && column.buildingId === building.id,
                ).length;
                if (span > 0) {
                    spans.push({
                        key: `${day.date}-${building.id}`,
                        name: building.name,
                        span,
                    });
                }
            });
        });
        return spans;
    }, [days, buildings, visibleScheduleColumns]);

    const navigateWeek = (weekStart) => {
        router.get(route('weekly-schedule.index'), { week: weekStart }, { preserveState: false, replace: true });
    };

    const switchScheduleView = (view) => {
        setScheduleView(view);
    };

    const handleGroupDayClick = (dayDate) => {
        if (!canWrite || !week.is_editable || !selectedGroupId) {
            return;
        }
        openCreateModal({
            lesson_date: dayDate,
            course_group_id: selectedGroupId,
        });
    };

    const openCreateModal = (defaults = {}) => {
        lessonForm.setData({
            lesson_date: defaults.lesson_date ?? days[0]?.date ?? week.week_start_date,
            starts_at: defaults.starts_at ?? '09:00',
            ends_at: defaults.ends_at ?? '10:00',
            academy_building_id: defaults.academy_building_id ?? buildings[0]?.id ?? '',
            academy_room_id: defaults.academy_room_id ?? buildings[0]?.rooms?.[0]?.id ?? '',
            course_group_id: defaults.course_group_id ?? '',
            teacher_id: defaults.teacher_id ?? '',
            lesson_id: defaults.lesson_id ?? '',
            title: defaults.title ?? '',
            notes: defaults.notes ?? '',
            color: defaults.color ?? '',
            status: 'scheduled',
        });
        lessonForm.clearErrors();
        setEditingLesson(null);
        setShowLessonModal(true);
    };

    const submitLesson = (e) => {
        e.preventDefault();
        if (!canWrite || !week.is_editable) {
            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setShowLessonModal(false);
                setEditingLesson(null);
            },
        };

        if (editingLesson) {
            lessonForm.patch(route('weekly-schedule.lessons.update', [week.id, editingLesson.id]), options);
            return;
        }

        lessonForm.post(route('weekly-schedule.lessons.store', week.id), options);
    };

    const publishSchedule = () => {
        router.post(route('weekly-schedule.publish', week.id), {}, {
            preserveScroll: true,
            onSuccess: () => setShowPublishModal(false),
        });
    };

    const copyPreviousWeek = (replace = false) => {
        router.post(route('weekly-schedule.copy-previous', week.id), { replace }, {
            preserveScroll: true,
            onSuccess: () => setShowCopyModal(false),
        });
    };

    const handleCopyPrevious = () => {
        if ((week.lessons_count ?? 0) > 0) {
            setShowCopyModal(true);
            return;
        }
        copyPreviousWeek(false);
    };

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const clearTimelineForAi = () => new Promise((resolve, reject) => {
        router.post(route('weekly-schedule.clear', week.id), {}, {
            preserveScroll: true,
            preserveState: true,
            only: ['week', 'scheduledLessons', 'groupLessonCards', 'conflicts', 'unscheduledGroups'],
            onSuccess: () => resolve(),
            onError: () => reject(new Error(t.clearTimelineConfirmText)),
        });
    });

    const requestAiSchedule = async (allowPartial = false, options = {}) => {
        const prompt = options.prompt ?? aiPrompt;
        const mode = options.mode ?? aiMode;
        aiPendingRequestRef.current = { prompt, mode };

        if (prompt.trim()) {
            rememberAiPrompt(prompt);
        }

        setAiProcessing(true);
        setAiProcessingPhase(null);
        setAiError('');

        try {
            if (mode === 'create' && !allowPartial) {
                setAiProcessingPhase('clearing');
                await clearTimelineForAi();
            }

            setAiProcessingPhase('scheduling');

            const response = await fetch(route('weekly-schedule.ai-schedule', week.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    prompt,
                    mode,
                    allow_partial: allowPartial,
                }),
            });

            const rawBody = await response.text();
            let payload = null;
            try {
                payload = rawBody ? JSON.parse(rawBody) : null;
            } catch {
                throw new Error(
                    response.ok
                        ? 'AI scheduling returned an invalid response.'
                        : `AI scheduling failed (HTTP ${response.status}). The server may have timed out — try again.`,
                );
            }

            if (!response.ok || payload?.status === 'error') {
                throw new Error(payload?.message || `AI scheduling failed (HTTP ${response.status}).`);
            }

            if (payload.status === 'needs_partial_confirmation') {
                setAiPartialMessage(payload.message || '');
                setShowAiConfirmModal(false);
                setShowAiPartialModal(true);
                return;
            }

            setShowAiConfirmModal(false);
            setShowAiPartialModal(false);
            setShowAiModal(false);
            setAiPrompt(prompt);
            setAiMode(mode);
            setAiReport(payload.report || '');
            setAiRecommendations(payload.recommendations || []);
            setAiWarnings(payload.warnings || []);
            setShowAiReportModal(true);

            router.reload({
                only: ['week', 'scheduledLessons', 'groupLessonCards', 'conflicts', 'unscheduledGroups'],
                preserveScroll: true,
                preserveState: true,
            });
        } catch (error) {
            setAiError(error instanceof Error ? error.message : 'AI scheduling failed.');
        } finally {
            setAiProcessing(false);
            setAiProcessingPhase(null);
        }
    };

    const aiBusyLabel = aiProcessingPhase === 'clearing'
        ? t.aiClearingTimeline
        : t.aiProcessing;

    const handleAiStart = () => {
        if (!aiConnected || aiProcessing) {
            return;
        }
        setShowAiConfirmModal(true);
    };

    const handleAiConfirm = () => {
        requestAiSchedule(false);
    };

    const handleAiPartialConfirm = () => {
        requestAiSchedule(true, aiPendingRequestRef.current);
    };

    const handleAiApplyRecommendations = () => {
        if (aiProcessing || aiRecommendations.length === 0) {
            return;
        }

        const recommendationText = aiRecommendations
            .map((item, index) => `${index + 1}. ${item}`)
            .join('\n');

        const enhancedPrompt = [
            aiPrompt.trim(),
            t.aiRecommendationsApplyPrefix,
            recommendationText,
        ].filter(Boolean).join('\n\n');

        requestAiSchedule(false, {
            prompt: enhancedPrompt,
            mode: 'create',
        });
    };

    const clearTimeline = () => {
        router.post(route('weekly-schedule.clear', week.id), {}, {
            preserveScroll: true,
            onSuccess: () => setShowClearTimelineModal(false),
        });
    };

    const handleRowClick = (dayDate, buildingId, roomId) => {
        if (!canWrite || !week.is_editable) {
            return;
        }
        openCreateModal({
            lesson_date: dayDate,
            academy_building_id: buildingId,
            academy_room_id: roomId,
        });
    };

    const handleScheduledLessonDragStart = (event, lesson) => {
        if (!canWrite || !week.is_editable) {
            event.preventDefault();
            return;
        }

        if (event.dataTransfer?.setDragImage && event.currentTarget instanceof HTMLElement) {
            event.dataTransfer.setDragImage(event.currentTarget, 0, 0);
        }

        event.dataTransfer.setData('application/x-aub-drag-type', 'scheduled');
        event.dataTransfer.setData('application/json', JSON.stringify({
            id: lesson.id,
            duration_minutes: timeToMinutes(lesson.ends_at) - timeToMinutes(lesson.starts_at),
        }));
        event.dataTransfer.effectAllowed = 'move';
    };

    const handleScheduledLessonDropToPool = (payload) => {
        if (!canWrite || !week.is_editable) {
            return;
        }

        const scheduledLesson = scheduledLessons.find((lesson) => lesson.id === payload?.id);
        if (!scheduledLesson) {
            return;
        }

        router.delete(route('weekly-schedule.lessons.destroy', [week.id, scheduledLesson.id]), {
            preserveScroll: true,
        });
    };

    const handleCellDragOver = (event, cellKey) => {
        if (!canWrite || !week.is_editable) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        setDragOverCellKey(cellKey);
    };

    const handleCellDragLeave = (cellKey) => {
        setDragOverCellKey((current) => (current === cellKey ? null : current));
    };

    const handleCellDrop = (event, column) => {
        event.preventDefault();
        setDragOverCellKey(null);

        if (!canWrite || !week.is_editable) {
            return;
        }

        const dragType = event.dataTransfer.getData('application/x-aub-drag-type');
        const payloadRaw = event.dataTransfer.getData('application/json');
        if (!payloadRaw) {
            return;
        }

        const startsAt = snapTimeFromDrop(
            event.clientY,
            event.currentTarget,
            gridStart,
            gridSpan,
        );

        if (dragType === 'card') {
            const card = JSON.parse(payloadRaw);
            if (Number(card.remaining_minutes ?? 0) <= 0) {
                return;
            }

            const defaultHours = Math.min(60, Number(card.remaining_minutes ?? 0));
            const resolvedStartAt = resolveNextFreeStart({
                lessonDate: column.dayDate,
                startsAt,
                durationMinutes: defaultHours,
                courseGroupId: card.course_group_id,
                teacherId: card.teacher_id,
                roomId: column.roomId,
                scheduledLessons,
                gridStart,
                gridSpan,
            }) ?? startsAt;

            setDropPlacement({
                card,
                lesson_date: column.dayDate,
                academy_building_id: column.buildingId,
                academy_room_id: column.roomId,
                starts_at: resolvedStartAt,
                building_name: column.buildingName,
                room_name: column.roomName,
                day_label: column.dayLabel,
            });
            setDropHours(String(defaultHours));
            setDropTeacherId(String(card.teacher_id));
            return;
        }

        if (dragType === 'scheduled') {
            const payload = JSON.parse(payloadRaw);
            const scheduledLesson = scheduledLessons.find((lesson) => lesson.id === payload.id);
            if (!scheduledLesson) {
                return;
            }

            const durationMinutes = payload.duration_minutes
                ?? (timeToMinutes(scheduledLesson.ends_at) - timeToMinutes(scheduledLesson.starts_at));

            router.patch(route('weekly-schedule.lessons.update', [week.id, scheduledLesson.id]), {
                lesson_date: column.dayDate,
                starts_at: startsAt,
                ends_at: minutesToTime(timeToMinutes(startsAt) + durationMinutes),
                academy_building_id: column.buildingId,
                academy_room_id: column.roomId,
                course_group_id: scheduledLesson.course_group_id ?? '',
                teacher_id: scheduledLesson.teacher_id ?? '',
                lesson_id: scheduledLesson.lesson_id ?? '',
                title: scheduledLesson.title ?? '',
                notes: scheduledLesson.notes ?? '',
                color: scheduledLesson.color ?? '',
                status: scheduledLesson.status ?? 'scheduled',
            }, {
                preserveScroll: true,
            });
        }
    };

    const submitDropPlacement = (event) => {
        event.preventDefault();

        if (!dropPlacement || dropProcessing) {
            return;
        }

        const hours = Number(dropHours);
        const remaining = Number(dropPlacement.card.remaining_minutes ?? 0);

        if (!Number.isFinite(hours) || hours < 30 || hours > remaining || hours % 30 !== 0) {
            return;
        }

        setDropProcessing(true);

        const resolvedStartAt = resolveNextFreeStart({
            lessonDate: dropPlacement.lesson_date,
            startsAt: dropPlacement.starts_at,
            durationMinutes: hours,
            courseGroupId: dropPlacement.card.course_group_id,
            teacherId: dropTeacherId,
            roomId: dropPlacement.academy_room_id,
            scheduledLessons,
            gridStart,
            gridSpan,
        });

        if (!resolvedStartAt) {
            setDropProcessing(false);
            return;
        }

        router.post(route('weekly-schedule.lessons.store', week.id), {
            lesson_date: dropPlacement.lesson_date,
            starts_at: resolvedStartAt,
            ends_at: addMinutesToTime(resolvedStartAt, hours),
            academy_building_id: dropPlacement.academy_building_id,
            academy_room_id: dropPlacement.academy_room_id,
            course_group_id: dropPlacement.card.course_group_id,
            teacher_id: dropTeacherId,
            lesson_id: dropPlacement.card.lesson_id,
            title: '',
            notes: '',
            color: '',
            status: 'scheduled',
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setDropPlacement(null);
                setDropProcessing(false);
            },
            onError: () => setDropProcessing(false),
            onFinish: () => setDropProcessing(false),
        });
    };

    const viewToggle = (
        <nav
            className="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 shadow-sm"
            role="tablist"
        >
            <button
                type="button"
                role="tab"
                aria-selected={scheduleView === 'general'}
                onClick={() => switchScheduleView('general')}
                className={`min-w-[6.5rem] rounded-lg px-4 py-2 text-sm font-semibold transition sm:min-w-[7rem] sm:px-5 sm:py-2.5 ${
                    scheduleView === 'general'
                        ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                        : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
                }`}
            >
                {t.viewGeneral}
            </button>
            <button
                type="button"
                role="tab"
                aria-selected={scheduleView === 'group'}
                onClick={() => switchScheduleView('group')}
                className={`min-w-[6.5rem] rounded-lg px-4 py-2 text-sm font-semibold transition sm:min-w-[7rem] sm:px-5 sm:py-2.5 ${
                    scheduleView === 'group'
                        ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                        : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
                }`}
            >
                {t.viewGroup}
            </button>
        </nav>
    );

    return (
        <AdminLayout
            title={formatWeekRange(week.week_start_date, week.week_end_date, locale)}
            headerCenter={viewToggle}
        >
            <Head title={t.pageTitle} />

            <div className="space-y-4">
                <section className="app-widget p-4">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="text-sm text-slate-600">
                                {t.weekLabel}: {formatWeekRange(week.week_start_date, week.week_end_date, locale)}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <button type="button" onClick={() => navigateWeek(week.prev_week_start)} className="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                                <ChevronLeft className="h-4 w-4" />
                                {t.prevWeek}
                            </button>
                            <button type="button" onClick={() => navigateWeek(week.next_week_start)} className="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">
                                {t.nextWeek}
                                <ChevronRight className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {scheduleView === 'general' ? (
                        <div className="mt-4 flex flex-wrap items-center gap-2">
                            {canWrite && week.is_editable && (
                                <>
                                    <ToolbarIconButton
                                        onClick={() => openCreateModal()}
                                        title={t.addLesson}
                                        variant="dark"
                                    >
                                        <Plus className="h-4 w-4" />
                                    </ToolbarIconButton>
                                    <button type="button" onClick={handleCopyPrevious} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium hover:bg-slate-50">
                                        <Copy className="h-4 w-4" />
                                        {t.copyPrevious}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowAiModal(true)}
                                        className="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-100"
                                    >
                                        <Sparkles className="h-4 w-4" />
                                        {t.aiAssistant}
                                    </button>
                                    <button type="button" onClick={() => setShowPublishModal(true)} className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                        {t.publish}
                                    </button>
                                </>
                            )}

                            <div className="ml-auto flex flex-wrap items-center gap-2">
                                {canWrite && week.is_editable && (
                                    <ToolbarIconButton
                                        onClick={() => setShowConflictsModal(true)}
                                        title={t.checkConflicts}
                                        variant="amber"
                                        badge={conflicts.length}
                                    >
                                        <AlertTriangle className="h-4 w-4" />
                                    </ToolbarIconButton>
                                )}
                                <ToolbarIconButton
                                    onClick={() => {}}
                                    disabled={!canExportPdf}
                                    title={canExportPdf ? t.exportPdf : t.exportPdfSoon}
                                    variant="muted"
                                >
                                    <FileDown className="h-4 w-4" />
                                </ToolbarIconButton>
                                {canWrite && week.is_editable && (
                                    <>
                                        <ToolbarIconButton
                                            onClick={() => setShowWeekSettingsModal(true)}
                                            title={t.weekSettings}
                                        >
                                            <SlidersHorizontal className="h-4 w-4" />
                                        </ToolbarIconButton>
                                        <ToolbarIconButton
                                            onClick={() => setShowClearTimelineModal(true)}
                                            title={t.clearTimeline}
                                            variant="red"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </ToolbarIconButton>
                                    </>
                                )}
                            </div>
                        </div>
                    ) : (
                        <div className="mt-4 flex flex-wrap items-center gap-2">
                            {canWrite && week.is_editable && (
                                <ToolbarIconButton
                                    onClick={() => openCreateModal(
                                        selectedGroupId ? { course_group_id: selectedGroupId } : {},
                                    )}
                                    title={t.addLesson}
                                    variant="dark"
                                >
                                    <Plus className="h-4 w-4" />
                                </ToolbarIconButton>
                            )}
                            <label className="block min-w-[16rem] max-w-xl flex-1 space-y-1">
                                <span className="sr-only">{t.selectGroupTitle}</span>
                                <select
                                    value={selectedGroupId ?? ''}
                                    onChange={(event) => {
                                        const nextId = Number(event.target.value);
                                        setSelectedGroupId(Number.isFinite(nextId) && nextId > 0 ? nextId : null);
                                    }}
                                    className={inputClass}
                                >
                                    <option value="">{t.selectGroupTitle}</option>
                                    {groups.map((group) => (
                                        <option key={group.id} value={group.id}>
                                            {group.name}
                                            {[group.course_name, group.discipline].filter(Boolean).length > 0
                                                ? ` — ${[group.course_name, group.discipline].filter(Boolean).join(' · ')}`
                                                : ''}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            {canWrite && week.is_editable && (
                                <button type="button" onClick={() => setShowPublishModal(true)} className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                    {t.publish}
                                </button>
                            )}
                        </div>
                    )}

                    {(errors?.publish || errors?.copy || errors?.lesson) && (
                        <div className="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {errors.publish || errors.copy || errors.lesson}
                        </div>
                    )}
                </section>

                {scheduleView === 'general' && (
                <LessonPalette
                    t={t}
                    groups={groups}
                    cards={groupLessonCards}
                    filter={paletteFilter}
                    onFilterChange={setPaletteFilter}
                    disabled={!canWrite || !week.is_editable}
                    allowDropScheduled={canWrite && week.is_editable}
                    onDropScheduled={handleScheduledLessonDropToPool}
                />
                )}

                {scheduleView === 'group' && selectedGroup && (
                    <section className="app-widget overflow-hidden p-0">
                        <div className="overflow-x-auto">
                            <div
                                style={{
                                    minWidth: TIME_COLUMN_WIDTH + (Math.max(1, days.length) * GROUP_DAY_COLUMN_MIN_WIDTH),
                                }}
                            >
                                <div
                                    className="sticky top-0 z-30 grid border-b border-slate-200 bg-slate-50"
                                    style={{
                                        gridTemplateColumns: `${TIME_COLUMN_WIDTH}px repeat(${Math.max(1, days.length)}, minmax(${GROUP_DAY_COLUMN_MIN_WIDTH}px, 1fr))`,
                                    }}
                                >
                                    <div className="sticky left-0 z-40 flex h-8 items-center justify-center border-r border-slate-200 bg-slate-50 px-1 text-center text-[10px] font-semibold uppercase text-slate-500">
                                        {t.day}
                                    </div>
                                    {days.map((day) => (
                                        <div
                                            key={`group-day-${day.date}`}
                                            className="flex h-8 items-center justify-center border-r border-slate-200 px-1 text-center text-[11px] font-semibold uppercase"
                                            style={{
                                                backgroundColor: dayHeaderColors[day.date] ?? DEFAULT_DAY_HEADER_COLOR,
                                                color: getContrastColor(dayHeaderColors[day.date] ?? DEFAULT_DAY_HEADER_COLOR),
                                            }}
                                        >
                                            {day.label}
                                        </div>
                                    ))}
                                </div>

                                <div
                                    className="grid"
                                    style={{
                                        gridTemplateColumns: `${TIME_COLUMN_WIDTH}px repeat(${Math.max(1, days.length)}, minmax(${GROUP_DAY_COLUMN_MIN_WIDTH}px, 1fr))`,
                                    }}
                                >
                                    <div className="sticky left-0 z-20 border-r border-slate-200 bg-slate-50 shadow-[2px_0_0_0_rgba(226,232,240,1)]">
                                        {timeSlots.map((slot) => (
                                            <div
                                                key={`group-time-${slot}`}
                                                className="flex items-start justify-end border-b border-slate-100 bg-slate-50 px-1 pt-0.5 text-[10px] text-slate-500"
                                                style={{ height: SLOT_HEIGHT_PX }}
                                            >
                                                {slot}
                                            </div>
                                        ))}
                                    </div>

                                    {days.map((day) => {
                                        const cellLessons = lessonsByGroupDate[`${day.date}:${selectedGroup.id}`] ?? [];
                                        return (
                                            <div
                                                key={`group-cell-${day.date}`}
                                                className="relative border-r border-slate-200 bg-white text-left hover:bg-slate-50/60"
                                                style={{ height: gridHeight }}
                                            >
                                                {canWrite && week.is_editable && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleGroupDayClick(day.date)}
                                                        className="absolute inset-0 z-0"
                                                        aria-label={t.clickToAdd}
                                                    />
                                                )}
                                                {timeSlots.map((slot, index) => (
                                                    <div
                                                        key={`group-slot-${day.date}-${slot}`}
                                                        className="pointer-events-none absolute inset-x-0 border-b border-slate-50"
                                                        style={{ top: index * SLOT_HEIGHT_PX, height: SLOT_HEIGHT_PX }}
                                                    />
                                                ))}
                                                {cellLessons.map((lesson) => (
                                                    <TimelineLessonBlockWrapper
                                                        key={lesson.id}
                                                        lesson={{
                                                            ...lesson,
                                                            group_name: [lesson.room_name, lesson.building_name]
                                                                .filter(Boolean)
                                                                .join(' · ') || '—',
                                                        }}
                                                        weekId={week.id}
                                                        gridStart={gridStart}
                                                        gridSpan={gridSpan}
                                                        gridHeight={gridHeight}
                                                        canEdit={canWrite && week.is_editable}
                                                        draggable={false}
                                                        t={t}
                                                    />
                                                ))}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                        {selectedGroupLessons.length === 0 && (
                            <div className="border-t border-slate-200 px-4 py-3 text-sm text-slate-500">
                                {t.groupScheduleEmpty}
                            </div>
                        )}
                    </section>
                )}

                {scheduleView === 'general' && (
                <div>
                    <section className="app-widget overflow-hidden p-0">
                        <div className="overflow-x-auto">
                            <div
                                className="min-w-[1300px]"
                                style={{
                                    minWidth: TIME_COLUMN_WIDTH + (Math.max(1, visibleScheduleColumns.length) * ROOM_COLUMN_MIN_WIDTH),
                                }}
                            >
                                <div
                                    className="sticky top-0 z-30 grid border-b border-slate-200 bg-slate-50"
                                    style={{
                                        gridTemplateColumns: `${TIME_COLUMN_WIDTH}px repeat(${Math.max(1, visibleScheduleColumns.length)}, minmax(${ROOM_COLUMN_MIN_WIDTH}px, 1fr))`,
                                    }}
                                >
                                    <div className="sticky left-0 z-40 flex h-8 items-center justify-center border-r border-slate-200 bg-slate-50 px-1 text-center text-[10px] font-semibold uppercase text-slate-500">
                                        Giorno
                                    </div>
                                    {dayColumnSpans.length > 0 ? dayColumnSpans.map((day) => (
                                        <div
                                            key={day.date}
                                            className="flex h-8 items-center justify-center border-r border-slate-200 px-1 text-center text-[11px] font-semibold uppercase"
                                            style={{
                                                gridColumn: `span ${Math.max(1, day.span)}`,
                                                backgroundColor: dayHeaderColors[day.date] ?? DEFAULT_DAY_HEADER_COLOR,
                                                color: getContrastColor(dayHeaderColors[day.date] ?? DEFAULT_DAY_HEADER_COLOR),
                                            }}
                                        >
                                            {day.label}
                                        </div>
                                    )) : (
                                        <div className="flex h-8 items-center justify-center border-r border-slate-200 bg-lime-300/70 px-1 text-center text-[11px] font-semibold uppercase text-slate-800">
                                            {t.noHallsVisible}
                                        </div>
                                    )}
                                </div>

                                <div
                                    className="sticky top-8 z-20 grid border-b border-slate-200 bg-slate-100"
                                    style={{
                                        gridTemplateColumns: `${TIME_COLUMN_WIDTH}px repeat(${Math.max(1, visibleScheduleColumns.length)}, minmax(${ROOM_COLUMN_MIN_WIDTH}px, 1fr))`,
                                    }}
                                >
                                    <div className="sticky left-0 z-40 flex h-8 items-center justify-center border-r border-slate-200 bg-slate-100 px-1 text-center text-[10px] font-semibold uppercase text-slate-500">
                                        Sede
                                    </div>
                                    {buildingColumnSpans.map((building) => (
                                        <div
                                            key={building.key}
                                            className="flex h-8 items-center justify-center border-r border-slate-200 px-1 text-center text-[10px] font-semibold uppercase text-slate-700"
                                            style={{ gridColumn: `span ${building.span}` }}
                                        >
                                            {building.name}
                                        </div>
                                    ))}
                                </div>

                                <div
                                    className="sticky top-16 z-20 grid border-b border-slate-200 bg-white"
                                    style={{
                                        gridTemplateColumns: `${TIME_COLUMN_WIDTH}px repeat(${Math.max(1, visibleScheduleColumns.length)}, minmax(${ROOM_COLUMN_MIN_WIDTH}px, 1fr))`,
                                    }}
                                >
                                    <div className="sticky left-0 z-40 flex h-8 items-center justify-center border-r border-slate-200 bg-white px-1 text-center text-[10px] font-semibold uppercase text-slate-500">
                                        Orario
                                    </div>
                                    {visibleScheduleColumns.length > 0 ? visibleScheduleColumns.map((column) => (
                                        <div
                                            key={`room-${column.key}`}
                                            className="flex h-8 items-center justify-center border-r border-slate-200 px-1 text-center text-[10px] font-semibold uppercase text-slate-700"
                                        >
                                            {column.roomName}
                                        </div>
                                    )) : (
                                        <div className="flex h-8 items-center justify-center border-r border-slate-200 px-1 text-center text-[10px] font-semibold uppercase text-slate-700">
                                            —
                                        </div>
                                    )}
                                </div>

                                <div
                                    className="grid"
                                    style={{
                                        gridTemplateColumns: `${TIME_COLUMN_WIDTH}px repeat(${Math.max(1, visibleScheduleColumns.length)}, minmax(${ROOM_COLUMN_MIN_WIDTH}px, 1fr))`,
                                    }}
                                >
                                    <div className="sticky left-0 z-20 border-r border-slate-200 bg-slate-50 shadow-[2px_0_0_0_rgba(226,232,240,1)]">
                                        {timeSlots.map((slot) => (
                                            <div
                                                key={`time-${slot}`}
                                                className="flex items-start justify-end border-b border-slate-100 bg-slate-50 px-1 pt-0.5 text-[10px] text-slate-500"
                                                style={{ height: SLOT_HEIGHT_PX }}
                                            >
                                                {slot}
                                            </div>
                                        ))}
                                    </div>

                                    {visibleScheduleColumns.map((column) => {
                                        const cellLessons = lessonsByRoomDate[`${column.dayDate}:${column.roomId}`] ?? [];
                                        return (
                                            <div
                                                key={`cell-${column.key}`}
                                                onDragOver={(event) => handleCellDragOver(event, column.key)}
                                                onDragLeave={() => handleCellDragLeave(column.key)}
                                                onDrop={(event) => handleCellDrop(event, column)}
                                                className={`relative border-r border-slate-200 text-left ${
                                                    dragOverCellKey === column.key
                                                        ? 'bg-indigo-50/70 ring-2 ring-inset ring-indigo-400'
                                                        : 'bg-white hover:bg-slate-50/60'
                                                }`}
                                                style={{ height: gridHeight }}
                                            >
                                                {canWrite && week.is_editable && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleRowClick(column.dayDate, column.buildingId, column.roomId)}
                                                        className="absolute inset-0 z-0"
                                                        aria-label={t.clickToAdd}
                                                    />
                                                )}
                                                {timeSlots.map((slot, index) => (
                                                    <div
                                                        key={`${column.key}-${slot}`}
                                                        className="pointer-events-none absolute inset-x-0 border-b border-slate-50"
                                                        style={{ top: index * SLOT_HEIGHT_PX, height: SLOT_HEIGHT_PX }}
                                                    />
                                                ))}
                                                {cellLessons.map((lesson) => (
                                                    <TimelineLessonBlockWrapper
                                                        key={lesson.id}
                                                        lesson={lesson}
                                                        weekId={week.id}
                                                        gridStart={gridStart}
                                                        gridSpan={gridSpan}
                                                        gridHeight={gridHeight}
                                                        canEdit={canWrite && week.is_editable}
                                                        draggable={canWrite && week.is_editable}
                                                        onDragStart={handleScheduledLessonDragStart}
                                                        t={t}
                                                    />
                                                ))}
                                            </div>
                                        );
                                    })}
                                    {visibleScheduleColumns.length === 0 && (
                                        <div
                                            className="flex items-center justify-center border-r border-slate-200 bg-white text-xs font-medium text-slate-500"
                                            style={{ height: gridHeight }}
                                        >
                                            {t.noHallsVisible}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                )}
            </div>

            {dropPlacement && (
                <Modal title={t.dropLessonTitle} onClose={() => setDropPlacement(null)} wide>
                    <form onSubmit={submitDropPlacement} className="space-y-4">
                        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <div className="font-semibold text-slate-900">{dropPlacement.card.lesson_name}</div>
                            <div>{dropPlacement.card.group_name}</div>
                            <div className="mt-1 text-xs text-slate-500">
                                {t.dropPlacementLabel}: {dropPlacement.day_label}, {dropPlacement.starts_at}, {dropPlacement.room_name} ({dropPlacement.building_name})
                            </div>
                        </div>

                        <Field label={t.dropHoursLabel}>
                            <input
                                type="number"
                                min="30"
                                max={dropPlacement.card.remaining_minutes}
                                step="30"
                                value={dropHours}
                                onChange={(event) => setDropHours(event.target.value)}
                                className={inputClass}
                                required
                            />
                            <p className="mt-1 text-xs text-slate-500">
                                {t.dropHoursHint
                                    .replace('{remaining}', String(dropPlacement.card.remaining_minutes))
                                    .replace('{weekly}', String(dropPlacement.card.weekly_minutes))}
                            </p>
                        </Field>

                        <Field label={t.dropTeacherLabel}>
                            <select
                                value={dropTeacherId}
                                onChange={(event) => setDropTeacherId(event.target.value)}
                                className={inputClass}
                                required
                            >
                                {(dropPlacement.card.teachers ?? []).map((teacher) => (
                                    <option key={teacher.id} value={teacher.id}>{teacher.name}</option>
                                ))}
                            </select>
                        </Field>

                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setDropPlacement(null)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={dropProcessing}
                                className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.placeLesson}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {showLessonModal && (
                <Modal title={editingLesson ? t.editLesson : t.addLesson} onClose={() => setShowLessonModal(false)} wide>
                    <form onSubmit={submitLesson} className="space-y-4">
                        <fieldset disabled={!canWrite || !week.is_editable} className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label={t.day}>
                                    <select value={lessonForm.data.lesson_date} onChange={(e) => lessonForm.setData('lesson_date', e.target.value)} className={inputClass}>
                                        {days.map((day) => (
                                            <option key={day.date} value={day.date}>{day.label} ({day.date})</option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label={t.group}>
                                    <select value={lessonForm.data.course_group_id} onChange={(e) => lessonForm.setData('course_group_id', e.target.value)} className={inputClass}>
                                        <option value="">—</option>
                                        {groups.map((group) => (
                                            <option key={group.id} value={group.id}>{group.name} {group.course_name ? `(${group.course_name})` : ''}</option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label={t.subject}>
                                    <select value={lessonForm.data.lesson_id} onChange={(e) => lessonForm.setData('lesson_id', e.target.value)} className={inputClass}>
                                        <option value="">—</option>
                                        {subjects.map((subject) => (
                                            <option key={subject.id} value={subject.id}>{subject.name}</option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label={t.teacher}>
                                    <select value={lessonForm.data.teacher_id} onChange={(e) => lessonForm.setData('teacher_id', e.target.value)} className={inputClass}>
                                        <option value="">—</option>
                                        {teachers.map((teacher) => (
                                            <option key={teacher.id} value={teacher.id}>{teacher.name}</option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label={t.building}>
                                    <select
                                        value={lessonForm.data.academy_building_id}
                                        onChange={(e) => {
                                            const buildingId = e.target.value;
                                            const building = buildings.find((item) => String(item.id) === String(buildingId));
                                            lessonForm.setData({
                                                ...lessonForm.data,
                                                academy_building_id: buildingId,
                                                academy_room_id: building?.rooms?.[0]?.id ?? '',
                                            });
                                        }}
                                        className={inputClass}
                                    >
                                        {buildings.map((building) => (
                                            <option key={building.id} value={building.id}>{building.name}</option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label={t.room}>
                                    <select value={lessonForm.data.academy_room_id} onChange={(e) => lessonForm.setData('academy_room_id', e.target.value)} className={inputClass}>
                                        {roomsForBuilding.map((room) => (
                                            <option key={room.id} value={room.id}>{room.name}</option>
                                        ))}
                                    </select>
                                </Field>
                                <Field label={t.start}>
                                    <input type="time" value={lessonForm.data.starts_at} onChange={(e) => lessonForm.setData('starts_at', e.target.value)} className={inputClass} step="1800" />
                                </Field>
                                <Field label={t.end}>
                                    <input type="time" value={lessonForm.data.ends_at} onChange={(e) => lessonForm.setData('ends_at', e.target.value)} className={inputClass} step="1800" />
                                </Field>
                            </div>
                            <Field label={t.notes}>
                                <textarea rows={3} value={lessonForm.data.notes} onChange={(e) => lessonForm.setData('notes', e.target.value)} className={inputClass} />
                            </Field>
                        </fieldset>
                        <div className="flex justify-end gap-2">
                            <button type="button" onClick={() => setShowLessonModal(false)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm">{t.cancel}</button>
                            {canWrite && week.is_editable && (
                                <button type="submit" disabled={lessonForm.processing} className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60">
                                    {t.save}
                                </button>
                            )}
                        </div>
                    </form>
                </Modal>
            )}

            {showPublishModal && (
                <Modal title={t.publishConfirmTitle} onClose={() => setShowPublishModal(false)}>
                    <p className="text-sm text-slate-600">{t.publishConfirmText}</p>
                    {conflicts.length > 0 && (
                        <p className="mt-2 text-sm font-medium text-red-600">{conflicts.length} conflict(s) detected.</p>
                    )}
                    <div className="mt-4 flex justify-end gap-2">
                        <button type="button" onClick={() => setShowPublishModal(false)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm">{t.cancel}</button>
                        <button type="button" onClick={publishSchedule} disabled={conflicts.length > 0} className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                            {t.publish}
                        </button>
                    </div>
                </Modal>
            )}

            {showCopyModal && (
                <Modal title={t.copyConfirmTitle} onClose={() => setShowCopyModal(false)}>
                    <p className="text-sm text-slate-600">{t.copyConfirmText.replace('{count}', String(week.lessons_count ?? 0))}</p>
                    <div className="mt-4 flex justify-end gap-2">
                        <button type="button" onClick={() => setShowCopyModal(false)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm">{t.cancel}</button>
                        <button type="button" onClick={() => copyPreviousWeek(true)} className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white">
                            {t.copyConfirm}
                        </button>
                    </div>
                </Modal>
            )}

            {showWeekSettingsModal && (
                <Modal title={t.weekSettingsTitle} onClose={() => setShowWeekSettingsModal(false)}>
                    <div className="space-y-4">
                        <div className="flex items-center justify-between gap-2">
                            <h4 className="text-sm font-semibold text-slate-700">{t.halls}</h4>
                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => setVisibleRoomIds(roomOptions.map((room) => room.id))}
                                    className="rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    {t.selectAll}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setVisibleRoomIds([])}
                                    className="rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    {t.clearAll}
                                </button>
                            </div>
                        </div>

                        <div className="max-h-80 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-2">
                            {roomOptions.map((room) => {
                                const checked = visibleRoomIds.includes(room.id);
                                return (
                                    <label
                                        key={room.id}
                                        className={`flex cursor-pointer items-start gap-2 rounded-md px-2 py-1.5 text-sm ${
                                            checked ? 'bg-indigo-50 text-indigo-900' : 'text-slate-700 hover:bg-slate-50'
                                        }`}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={checked}
                                            onChange={(e) => {
                                                if (e.target.checked) {
                                                    setVisibleRoomIds((prev) => Array.from(new Set([...prev, room.id])));
                                                    return;
                                                }
                                                setVisibleRoomIds((prev) => prev.filter((id) => id !== room.id));
                                            }}
                                            className="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span>
                                            <span className="font-medium">{room.name}</span>
                                            <span className="ml-1 text-xs text-slate-500">({room.building_name})</span>
                                        </span>
                                    </label>
                                );
                            })}
                        </div>

                        <div className="space-y-2">
                            <h4 className="text-sm font-semibold text-slate-700">{t.workingHours}</h4>
                            <div className="grid grid-cols-2 gap-3 rounded-lg border border-slate-200 p-3">
                                <label className="space-y-1 text-sm text-slate-700">
                                    <span className="font-medium">{t.minWorkTime}</span>
                                    <select
                                        value={workStartsAt}
                                        onChange={(e) => setWorkStartsAt(e.target.value)}
                                        className="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"
                                    >
                                        {WORK_TIME_OPTIONS.filter((time) => timeToMinutes(time) < timeToMinutes(workEndsAt)).map((time) => (
                                            <option key={`work-start-${time}`} value={time}>{time}</option>
                                        ))}
                                    </select>
                                </label>
                                <label className="space-y-1 text-sm text-slate-700">
                                    <span className="font-medium">{t.maxWorkTime}</span>
                                    <select
                                        value={workEndsAt}
                                        onChange={(e) => setWorkEndsAt(e.target.value)}
                                        className="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm"
                                    >
                                        {WORK_TIME_OPTIONS.filter((time) => timeToMinutes(time) > timeToMinutes(workStartsAt)).map((time) => (
                                            <option key={`work-end-${time}`} value={time}>{time}</option>
                                        ))}
                                    </select>
                                </label>
                            </div>
                            {(errors?.work_starts_at || errors?.work_ends_at) && (
                                <p className="text-xs text-red-600">
                                    {errors.work_starts_at || errors.work_ends_at}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <h4 className="text-sm font-semibold text-slate-700">{t.dayHeaderColors}</h4>
                                <button
                                    type="button"
                                    onClick={() => setDayHeaderColors(Object.fromEntries(days.map((day) => [day.date, DEFAULT_DAY_HEADER_COLOR])))}
                                    className="rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    {t.resetDayColors}
                                </button>
                            </div>
                            <div className="space-y-2 rounded-lg border border-slate-200 p-2">
                                {days.map((day) => (
                                    <label key={`day-color-${day.date}`} className="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                                        <span className="font-medium">{day.label}</span>
                                        <input
                                            type="color"
                                            value={dayHeaderColors[day.date] ?? DEFAULT_DAY_HEADER_COLOR}
                                            onChange={(e) => setDayHeaderColors((prev) => ({ ...prev, [day.date]: e.target.value }))}
                                            className="h-8 w-10 cursor-pointer rounded border border-slate-300 bg-white p-1"
                                        />
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <button
                                type="button"
                                onClick={saveWeekSettings}
                                disabled={savingWeekSettings}
                                className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white hover:bg-[#132033] disabled:opacity-60"
                            >
                                {t.save}
                            </button>
                        </div>
                    </div>
                </Modal>
            )}

            {showConflictsModal && (
                <Modal title={t.conflictsTitle} onClose={() => setShowConflictsModal(false)} wide>
                    {conflicts.length === 0 ? (
                        <p className="text-sm text-slate-600">{t.conflictsEmpty}</p>
                    ) : (
                        <ul className="space-y-2">
                            {conflicts.map((conflict, index) => (
                                <li key={`${conflict.type}-${index}`} className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                                    <span className="font-semibold">{conflict.type}</span>: {conflict.message}
                                    {conflict.lesson_label && <span className="block text-xs text-red-700">{conflict.lesson_label}</span>}
                                </li>
                            ))}
                        </ul>
                    )}
                </Modal>
            )}

            {showAiModal && (
                <Modal title={t.aiModalTitle} onClose={() => !aiProcessing && setShowAiModal(false)} wide>
                    <div className="space-y-4">
                        <div className={`rounded-lg border px-3 py-2 text-sm ${aiConnected ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-slate-50 text-slate-700'}`}>
                            <span className="font-semibold">{aiConnected ? t.aiStatusConnected : t.aiStatusDisconnected}</span>
                            {aiStatus.status_label && (
                                <span className="mt-1 block text-xs opacity-90">{aiStatus.status_label}</span>
                            )}
                            {!aiConnected && (
                                <span className="mt-1 block text-xs">{t.aiNotConnectedHint}</span>
                            )}
                        </div>

                        <div className="inline-flex rounded-lg border border-slate-300 p-1">
                            <button
                                type="button"
                                onClick={() => setAiMode('edit')}
                                className={`rounded-md px-3 py-1.5 text-sm font-medium ${aiMode === 'edit' ? 'bg-[#1A2B44] text-white' : 'text-slate-700 hover:bg-slate-50'}`}
                            >
                                {t.aiModeEdit}
                            </button>
                            <button
                                type="button"
                                onClick={() => setAiMode('create')}
                                className={`rounded-md px-3 py-1.5 text-sm font-medium ${aiMode === 'create' ? 'bg-[#1A2B44] text-white' : 'text-slate-700 hover:bg-slate-50'}`}
                            >
                                {t.aiModeCreate}
                            </button>
                        </div>
                        <p className="text-xs text-slate-500">
                            {aiMode === 'edit' ? t.aiModeEditHint : t.aiModeCreateHint}
                        </p>

                        <div>
                            <div className="mb-1 flex items-center justify-between gap-2">
                                <span className="text-sm font-medium text-slate-700">{t.aiPromptLabel}</span>
                                <div className="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        onClick={() => setShowAiInstructions(true)}
                                        disabled={aiProcessing}
                                        className="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                                    >
                                        <BookOpen className="h-3.5 w-3.5" />
                                        {t.aiInstructions}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowAiHistory((value) => !value)}
                                        disabled={aiProcessing}
                                        className="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                                    >
                                        <History className="h-3.5 w-3.5" />
                                        {t.aiPromptHistory}
                                    </button>
                                </div>
                            </div>

                            {showAiHistory && (
                                <div className="mb-2 max-h-40 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 p-2">
                                    {aiPromptHistory.length === 0 ? (
                                        <p className="px-1 py-1 text-xs text-slate-500">{t.aiPromptHistoryEmpty}</p>
                                    ) : (
                                        <ul className="space-y-1">
                                            {aiPromptHistory.map((item, index) => (
                                                <li key={`ai-prompt-history-${index}`}>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setAiPrompt(item);
                                                            setShowAiHistory(false);
                                                        }}
                                                        className="w-full rounded-md px-2 py-1.5 text-left text-xs text-slate-700 hover:bg-white"
                                                    >
                                                        <span className="line-clamp-4 whitespace-pre-wrap">{item}</span>
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            )}

                            <textarea
                                rows={5}
                                value={aiPrompt}
                                onChange={(event) => setAiPrompt(event.target.value)}
                                placeholder={t.aiPromptPlaceholder}
                                className={inputClass}
                                disabled={!aiConnected || aiProcessing}
                            />
                        </div>

                        {aiError && (
                            <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                {aiError}
                            </div>
                        )}

                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowAiModal(false)}
                                disabled={aiProcessing}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm disabled:opacity-60"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="button"
                                onClick={handleAiStart}
                                disabled={!aiConnected || aiProcessing}
                                className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                            >
                                <Sparkles className="h-4 w-4" />
                                {aiProcessing ? aiBusyLabel : t.aiStart}
                            </button>
                        </div>
                    </div>
                </Modal>
            )}

            {showAiInstructions && (
                <Modal
                    title={t.aiInstructionsTitle}
                    onClose={() => setShowAiInstructions(false)}
                    wide
                    elevated
                >
                    <div className="space-y-4">
                        <p className="text-sm text-slate-600">{t.aiInstructionsIntro}</p>
                        <ul className="space-y-3">
                            {(t.aiInstructionsLevers || []).map((lever) => (
                                <li key={lever.title} className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                                    <p className="text-sm font-semibold text-slate-800">{lever.title}</p>
                                    <p className="mt-1 text-sm text-slate-600">{lever.text}</p>
                                </li>
                            ))}
                        </ul>
                        <p className="text-xs text-slate-500">{t.aiInstructionsNote}</p>
                        <div className="flex justify-end">
                            <button
                                type="button"
                                onClick={() => setShowAiInstructions(false)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm"
                            >
                                {t.aiClose}
                            </button>
                        </div>
                    </div>
                </Modal>
            )}

            {showAiConfirmModal && (
                <Modal title={t.aiConfirmTitle} onClose={() => !aiProcessing && setShowAiConfirmModal(false)}>
                    <p className="text-sm text-slate-600">
                        {aiMode === 'edit' ? t.aiConfirmTextEdit : t.aiConfirmTextCreate}
                    </p>
                    {aiProcessing && (
                        <p className="mt-2 text-sm font-medium text-indigo-700">{aiBusyLabel}</p>
                    )}
                    <div className="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setShowAiConfirmModal(false)}
                            disabled={aiProcessing}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm disabled:opacity-60"
                        >
                            {t.cancel}
                        </button>
                        <button
                            type="button"
                            onClick={handleAiConfirm}
                            disabled={aiProcessing}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        >
                            {aiProcessing ? aiBusyLabel : t.aiStart}
                        </button>
                    </div>
                </Modal>
            )}

            {showAiPartialModal && (
                <Modal title={t.aiPartialTitle} onClose={() => !aiProcessing && setShowAiPartialModal(false)}>
                    <p className="text-sm text-slate-600">{aiPartialMessage}</p>
                    {aiProcessing && (
                        <p className="mt-2 text-sm font-medium text-indigo-700">{aiBusyLabel}</p>
                    )}
                    <div className="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setShowAiPartialModal(false)}
                            disabled={aiProcessing}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm disabled:opacity-60"
                        >
                            {t.cancel}
                        </button>
                        <button
                            type="button"
                            onClick={handleAiPartialConfirm}
                            disabled={aiProcessing}
                            className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        >
                            {aiProcessing ? aiBusyLabel : t.aiPartialConfirm}
                        </button>
                    </div>
                </Modal>
            )}

            {showAiReportModal && (
                <Modal
                    title={t.aiReportTitle}
                    onClose={() => {
                        setShowAiReportModal(false);
                        router.reload({ preserveScroll: true });
                    }}
                    wide
                >
                    <div className="max-h-[60vh] space-y-3 overflow-y-auto">
                        <div className="whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-800">
                            {aiReport || '—'}
                        </div>
                        {aiRecommendations.length > 0 && (
                            <div>
                                <h4 className="mb-2 text-sm font-semibold text-slate-800">{t.aiRecommendationsTitle}</h4>
                                <ul className="space-y-1">
                                    {aiRecommendations.map((recommendation, index) => (
                                        <li key={`ai-recommendation-${index}`} className="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm text-indigo-900">
                                            {recommendation}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                        {aiWarnings.length > 0 && (
                            <ul className="space-y-1">
                                {aiWarnings.map((warning, index) => (
                                    <li key={`ai-warning-${index}`} className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                        {warning}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                    <div className="mt-4 flex flex-wrap justify-end gap-2">
                        {aiRecommendations.length > 0 && (
                            <button
                                type="button"
                                onClick={handleAiApplyRecommendations}
                                disabled={aiProcessing}
                                className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                            >
                                <Sparkles className="h-4 w-4" />
                                {aiProcessing ? aiBusyLabel : t.aiApplyRecommendations}
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => {
                                setShowAiReportModal(false);
                                router.reload({ preserveScroll: true });
                            }}
                            disabled={aiProcessing}
                            className="rounded-lg bg-[#1A2B44] px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                        >
                            {t.aiClose}
                        </button>
                    </div>
                </Modal>
            )}

            {showClearTimelineModal && (
                <Modal title={t.clearTimelineConfirmTitle} onClose={() => setShowClearTimelineModal(false)}>
                    <p className="text-sm text-slate-600">{t.clearTimelineConfirmText}</p>
                    <div className="mt-4 flex justify-end gap-2">
                        <button type="button" onClick={() => setShowClearTimelineModal(false)} className="rounded-lg border border-slate-300 px-4 py-2 text-sm">{t.cancel}</button>
                        <button type="button" onClick={clearTimeline} className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">
                            {t.clearTimelineConfirm}
                        </button>
                    </div>
                </Modal>
            )}
        </AdminLayout>
    );
}

function Field({ label, children }) {
    return (
        <label className="block">
            <span className="mb-1 block text-sm font-medium text-slate-700">{label}</span>
            {children}
        </label>
    );
}

const inputClass = 'block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
