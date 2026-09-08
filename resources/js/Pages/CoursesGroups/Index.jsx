import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    Folder,
    FolderOpen,
    Plus,
    Settings,
    Trash2,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const DEFAULT_GROUP_COLOR = '#6366f1';

const TEXT = {
    it: {
        pageTitle: 'Corsi e gruppi',
        tabAcademy: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Carcano',
        addCourse: 'Aggiungi corso',
        addGroup: 'Aggiungi gruppo',
        addStudent: 'Aggiungi studenti',
        selectStudents: 'Seleziona studenti',
        selectAll: 'Seleziona tutti',
        deselectAll: 'Deseleziona tutti',
        selectedCount: 'Selezionati',
        chooseStudent: '— Scegli studente —',
        emptyCourses: 'Nessun corso. Clicca «Aggiungi corso» per iniziare.',
        courseName: 'Nome corso',
        courseSettings: 'Impostazioni corso',
        studyWindow: 'Finestra didattica',
        studyStartsAt: 'Dalle',
        studyEndsAt: 'Alle',
        studyWindowHint: 'Orario in cui le lezioni di questo corso possono essere distribuite (es. 08:00–13:00 o 13:00–18:00).',
        groupName: 'Nome gruppo',
        groupColor: 'Colore gruppo',
        groupSettings: 'Impostazioni gruppo',
        noStudentsAvailable: 'Tutti gli studenti sono già in questo gruppo.',
        noFreeStudents: 'Nessuno studente libero.',
        filterAvailable: 'Disponibili',
        filterAll: 'Tutti',
        alreadyInGroup: '{course} → {group}',
        confirmMoveTitle: 'Conferma trasferimento',
        confirmMoveMessage: 'Alcuni studenti selezionati sono già in un\'altra gruppo. Vuoi trasferirli qui?',
        confirmMove: 'Trasferisci',
        cancel: 'Annulla',
        save: 'Salva',
        add: 'Aggiungi',
        deleteCourse: 'Elimina corso',
        deleteGroup: 'Elimina gruppo',
        removeStudent: 'Rimuovi dallo gruppo',
        confirmDeleteCourse: 'Eliminare questo corso e tutti i gruppi?',
        confirmDeleteGroup: 'Eliminare questo gruppo?',
        confirmRemoveStudent: 'Rimuovere lo studente dal gruppo?',
        confirmDeleteTitle: 'Conferma eliminazione',
        confirm: 'Elimina',
        studentsCount: 'studenti',
        groupsCount: 'gruppi',
        viewStudents: 'Studenti',
        viewLessons: 'Lezioni',
        addLesson: 'Aggiungi lezioni',
        selectLessons: 'Seleziona lezioni',
        noLessonsAvailable: 'Tutte le lezioni sono già in questo gruppo.',
        noFreeLessons: 'Tutte le lezioni sono già in questo gruppo.',
        removeLesson: 'Rimuovi dal gruppo',
        confirmRemoveLesson: 'Rimuovere la lezione dal gruppo?',
        lessonsCount: 'lezioni',
        teacher: 'Insegnante',
        hours: 'Ore',
        chooseTeacher: '— Scegli insegnante —',
        hoursPlaceholder: 'es. 2',
        alreadyInThisGroup: 'Già in questo gruppo',
        lessonDetails: 'Dettagli lezione',
        lessonName: 'Nome lezione',
        description: 'Descrizione',
        course: 'Corso',
        group: 'Gruppo',
        availableTeachers: 'Insegnanti disponibili',
        noDescription: 'Nessuna descrizione',
        close: 'Chiudi',
        hoursPerWeek: 'Ore a settimana',
        hoursPerWeekShort: 'h/sett.',
        lessonDuration: 'Durata di una lezione',
        minutesShort: 'min',
        assignedTeachers: 'Insegnanti assegnati',
        addTeacher: 'Aggiungi insegnante',
        selectTeacher: '— Seleziona insegnante —',
        removeTeacher: 'Rimuovi insegnante',
    },
    uk: {
        pageTitle: 'Курси і групи',
        tabAcademy: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Carcano',
        addCourse: 'Додати курс',
        addGroup: 'Додати групу',
        addStudent: 'Додати студентів',
        selectStudents: 'Оберіть студентів',
        selectAll: 'Вибрати всіх',
        deselectAll: 'Зняти вибір',
        selectedCount: 'Обрано',
        chooseStudent: '— Оберіть студента —',
        emptyCourses: 'Курсів ще немає. Натисніть «Додати курс».',
        courseName: 'Назва курсу',
        courseSettings: 'Налаштування курсу',
        studyWindow: 'Навчальне вікно',
        studyStartsAt: 'З',
        studyEndsAt: 'До',
        studyWindowHint: 'Години, в які можна розподіляти уроки цього курсу (напр. 08:00–13:00 або 13:00–18:00).',
        groupName: 'Назва групи',
        groupColor: 'Колір групи',
        groupSettings: 'Налаштування групи',
        noStudentsAvailable: 'Усі студенти вже в цій групі.',
        noFreeStudents: 'Немає вільних студентів.',
        filterAvailable: 'Доступні',
        filterAll: 'Усі',
        alreadyInGroup: '{course} → {group}',
        confirmMoveTitle: 'Підтвердження перенесення',
        confirmMoveMessage: 'Деякі обрані студенти вже в іншій групі. Перенести їх сюди?',
        confirmMove: 'Перенести',
        cancel: 'Скасувати',
        save: 'Зберегти',
        add: 'Додати',
        deleteCourse: 'Видалити курс',
        deleteGroup: 'Видалити групу',
        removeStudent: 'Прибрати з групи',
        confirmDeleteCourse: 'Видалити цей курс і всі групи?',
        confirmDeleteGroup: 'Видалити цю групу?',
        confirmRemoveStudent: 'Прибрати студента з групи?',
        confirmDeleteTitle: 'Підтвердження видалення',
        confirm: 'Видалити',
        studentsCount: 'студентів',
        groupsCount: 'груп',
        viewStudents: 'Студенти',
        viewLessons: 'Уроки',
        addLesson: 'Додати уроки',
        selectLessons: 'Оберіть уроки',
        noLessonsAvailable: 'Усі уроки вже в цій групі.',
        noFreeLessons: 'Усі уроки вже в цій групі.',
        removeLesson: 'Прибрати з групи',
        confirmRemoveLesson: 'Прибрати урок з групи?',
        lessonsCount: 'уроків',
        teacher: 'Викладач',
        hours: 'Години',
        chooseTeacher: '— Оберіть викладача —',
        hoursPlaceholder: 'напр. 2',
        alreadyInThisGroup: 'Вже в цій групі',
        lessonDetails: 'Деталі уроку',
        lessonName: 'Назва уроку',
        description: 'Опис',
        course: 'Курс',
        group: 'Група',
        availableTeachers: 'Доступні викладачі',
        noDescription: 'Без опису',
        close: 'Закрити',
        hoursPerWeek: 'Години на тиждень',
        hoursPerWeekShort: 'г/тиж.',
        lessonDuration: 'Тривалість одного заняття',
        minutesShort: 'хв',
        assignedTeachers: 'Призначені викладачі',
        addTeacher: 'Додати викладача',
        selectTeacher: '— Оберіть викладача —',
        removeTeacher: 'Прибрати викладача',
    },
    en: {
        pageTitle: 'Courses and groups',
        tabAcademy: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Carcano',
        addCourse: 'Add course',
        addGroup: 'Add group',
        addStudent: 'Add students',
        selectStudents: 'Select students',
        selectAll: 'Select all',
        deselectAll: 'Deselect all',
        selectedCount: 'Selected',
        chooseStudent: '— Choose student —',
        emptyCourses: 'No courses yet. Click «Add course» to start.',
        courseName: 'Course name',
        courseSettings: 'Course settings',
        studyWindow: 'Study window',
        studyStartsAt: 'From',
        studyEndsAt: 'To',
        studyWindowHint: 'Hours when this course’s lessons may be scheduled (e.g. 08:00–13:00 or 13:00–18:00).',
        groupName: 'Group name',
        groupColor: 'Group color',
        groupSettings: 'Group settings',
        noStudentsAvailable: 'All students are already in this group.',
        noFreeStudents: 'No available students.',
        filterAvailable: 'Available',
        filterAll: 'All',
        alreadyInGroup: '{course} → {group}',
        confirmMoveTitle: 'Confirm transfer',
        confirmMoveMessage: 'Some selected students are already in another group. Move them here?',
        confirmMove: 'Transfer',
        cancel: 'Cancel',
        save: 'Save',
        add: 'Add',
        deleteCourse: 'Delete course',
        deleteGroup: 'Delete group',
        removeStudent: 'Remove from group',
        confirmDeleteCourse: 'Delete this course and all its groups?',
        confirmDeleteGroup: 'Delete this group?',
        confirmRemoveStudent: 'Remove student from group?',
        confirmDeleteTitle: 'Confirm deletion',
        confirm: 'Delete',
        studentsCount: 'students',
        groupsCount: 'groups',
        viewStudents: 'Students',
        viewLessons: 'Lessons',
        addLesson: 'Add lessons',
        selectLessons: 'Select lessons',
        noLessonsAvailable: 'All lessons are already in this group.',
        noFreeLessons: 'All lessons are already in this group.',
        removeLesson: 'Remove from group',
        confirmRemoveLesson: 'Remove lesson from group?',
        lessonsCount: 'lessons',
        teacher: 'Teacher',
        hours: 'Hours',
        chooseTeacher: '— Choose teacher —',
        hoursPlaceholder: 'e.g. 2',
        alreadyInThisGroup: 'Already in this group',
        lessonDetails: 'Lesson details',
        lessonName: 'Lesson name',
        description: 'Description',
        course: 'Course',
        group: 'Group',
        availableTeachers: 'Available teachers',
        noDescription: 'No description',
        close: 'Close',
        hoursPerWeek: 'Hours per week',
        hoursPerWeekShort: 'h/week',
        lessonDuration: 'Single lesson duration',
        minutesShort: 'min',
        assignedTeachers: 'Assigned teachers',
        addTeacher: 'Add teacher',
        selectTeacher: '— Select teacher —',
        removeTeacher: 'Remove teacher',
    },
    ru: {
        pageTitle: 'Курсы и группы',
        tabAcademy: 'AUB',
        tabTam: 'TAM',
        tabCarcano: 'Каркано',
        addCourse: 'Добавить курс',
        addGroup: 'Добавить группу',
        addStudent: 'Добавить студентов',
        selectStudents: 'Выберите студентов',
        selectAll: 'Выбрать всех',
        deselectAll: 'Снять выбор',
        selectedCount: 'Выбрано',
        chooseStudent: '— Выберите студента —',
        emptyCourses: 'Курсов пока нет. Нажмите «Добавить курс».',
        courseName: 'Название курса',
        courseSettings: 'Настройки курса',
        studyWindow: 'Учебное окно',
        studyStartsAt: 'С',
        studyEndsAt: 'До',
        studyWindowHint: 'Часы, в которые можно ставить уроки этого курса (напр. 08:00–13:00 или 13:00–18:00).',
        groupName: 'Название группы',
        groupColor: 'Цвет группы',
        groupSettings: 'Настройки группы',
        noStudentsAvailable: 'Все студенты уже в этой группе.',
        noFreeStudents: 'Нет свободных студентов.',
        filterAvailable: 'Доступные',
        filterAll: 'Все',
        alreadyInGroup: '{course} → {group}',
        confirmMoveTitle: 'Подтверждение переноса',
        confirmMoveMessage: 'Некоторые выбранные студенты уже записаны в другую группу. Перенести их сюда?',
        confirmMove: 'Перенести',
        cancel: 'Отмена',
        save: 'Сохранить',
        add: 'Добавить',
        deleteCourse: 'Удалить курс',
        deleteGroup: 'Удалить группу',
        removeStudent: 'Убрать из группы',
        confirmDeleteCourse: 'Удалить этот курс и все группы?',
        confirmDeleteGroup: 'Удалить эту группу?',
        confirmRemoveStudent: 'Убрать студента из группы?',
        confirmDeleteTitle: 'Подтверждение удаления',
        confirm: 'Удалить',
        studentsCount: 'студентов',
        groupsCount: 'групп',
        viewStudents: 'Студенты',
        viewLessons: 'Уроки',
        addLesson: 'Добавить уроки',
        selectLessons: 'Выберите уроки',
        noLessonsAvailable: 'Все уроки уже в этой группе.',
        noFreeLessons: 'Все предметы уже добавлены в эту группу.',
        removeLesson: 'Убрать из группы',
        confirmRemoveLesson: 'Убрать урок из группы?',
        lessonsCount: 'уроков',
        teacher: 'Преподаватель',
        hours: 'Часы',
        chooseTeacher: '— Выберите преподавателя —',
        hoursPlaceholder: 'напр. 2',
        alreadyInThisGroup: 'Уже в этой группе',
        lessonDetails: 'Данные урока',
        lessonName: 'Название урока',
        description: 'Описание',
        course: 'Курс',
        group: 'Группа',
        availableTeachers: 'Доступные преподаватели',
        noDescription: 'Без описания',
        close: 'Закрыть',
        hoursPerWeek: 'Часов в неделю',
        hoursPerWeekShort: 'ч/нед.',
        lessonDuration: 'Длительность одного занятия',
        minutesShort: 'мин',
        assignedTeachers: 'Назначенные преподаватели',
        addTeacher: 'Добавить преподавателя',
        selectTeacher: '— Выберите преподавателя —',
        removeTeacher: 'Убрать преподавателя',
    },
};

const TABS = ['academy', 'tam', 'carcano'];
const VIEW_MODES = ['students', 'lessons'];

function expandedStateStorageKey(tab, view) {
    return `courses-groups:expanded:${tab}:${view}`;
}

function loadExpandedState(tab, view) {
    if (typeof window === 'undefined') {
        return { courses: new Set(), groups: new Set() };
    }

    try {
        const raw = window.sessionStorage.getItem(expandedStateStorageKey(tab, view));
        if (!raw) {
            return { courses: new Set(), groups: new Set() };
        }

        const parsed = JSON.parse(raw);

        return {
            courses: new Set(parsed.courses ?? []),
            groups: new Set(parsed.groups ?? []),
        };
    } catch {
        return { courses: new Set(), groups: new Set() };
    }
}

function saveExpandedState(tab, view, expandedCourses, expandedGroups) {
    if (typeof window === 'undefined') {
        return;
    }

    window.sessionStorage.setItem(
        expandedStateStorageKey(tab, view),
        JSON.stringify({
            courses: [...expandedCourses],
            groups: [...expandedGroups],
        }),
    );
}

function formatAssignment(t, assignment) {
    return t.alreadyInGroup
        .replace('{course}', assignment.course_name)
        .replace('{group}', assignment.group_name);
}

function lessonAssignmentsElsewhere(lessonAssignments, lessonId, currentGroupId) {
    const assignments = lessonAssignments[lessonId];
    if (!assignments) {
        return [];
    }

    const list = Array.isArray(assignments) ? assignments : [assignments];

    return list.filter((assignment) => assignment.group_id !== currentGroupId);
}

function formatLessonDuration(minutes, t) {
    if (!minutes) {
        return null;
    }

    return `${minutes} ${t.minutesShort}`;
}

function formatLessonAssignmentsSummary(lesson, t) {
    const assignments = lesson.assignments?.length
        ? lesson.assignments
        : lesson.teacher_name
            ? [{ teacher_name: lesson.teacher_name, hours: lesson.hours }]
            : [];

    const durationLabel = formatLessonDuration(lesson.duration_minutes, t);
    const assignmentsLabel = assignments.length === 0
        ? ''
        : assignments
            .map((assignment) => `${assignment.teacher_name} · ${assignment.hours} ${t.hoursPerWeekShort}`)
            .join(', ');

    return [durationLabel, assignmentsLabel].filter(Boolean).join(' · ');
}

function teacherNameById(teachers, teacherId) {
    return teachers.find((teacher) => teacher.id === teacherId)?.name ?? `#${teacherId}`;
}

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

function StudentAvatar({ student, size = 'sm' }) {
    const sizeClass = size === 'md' ? 'h-8 w-8 text-xs' : 'h-7 w-7 text-[10px]';

    if (student.student_photo_path) {
        return (
            <img
                src={`/storage/${student.student_photo_path}`}
                alt={student.name}
                className={`${sizeClass} shrink-0 rounded-full object-cover ring-1 ring-slate-200`}
            />
        );
    }

    return (
        <div className={`${sizeClass} flex shrink-0 items-center justify-center rounded-full bg-[#1A2B44]/10 font-semibold text-[#1A2B44] ring-1 ring-slate-200`}>
            {getInitials(student.name)}
        </div>
    );
}

function getInitials(name) {
    const parts = (name || 'ST')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);

    return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'ST';
}

export default function CoursesGroupsIndex({
    tab = 'academy',
    view = 'students',
    courses = [],
    students = [],
    studentAssignments = {},
    catalogLessons = [],
    lessonAssignments = {},
}) {
    const { locale = 'it', auth } = usePage().props;
    const t = TEXT[locale] ?? TEXT.it;
    const canDelete = auth?.user?.can_delete === true;
    const canWrite = auth?.user?.can_write === true;
    const activeTab = TABS.includes(tab) ? tab : 'academy';
    const activeView = VIEW_MODES.includes(view) ? view : 'students';
    const isLessonsView = activeView === 'lessons';

    const [expandedCourses, setExpandedCourses] = useState(
        () => loadExpandedState(activeTab, activeView).courses,
    );
    const [expandedGroups, setExpandedGroups] = useState(
        () => loadExpandedState(activeTab, activeView).groups,
    );

    useEffect(() => {
        saveExpandedState(activeTab, activeView, expandedCourses, expandedGroups);
    }, [activeTab, activeView, expandedCourses, expandedGroups]);

    const [showCourseModal, setShowCourseModal] = useState(false);
    const [showCourseSettingsModal, setShowCourseSettingsModal] = useState(null);
    const [showGroupModal, setShowGroupModal] = useState(null);
    const [showGroupSettingsModal, setShowGroupSettingsModal] = useState(null);
    const [showStudentModal, setShowStudentModal] = useState(null);
    const [showLessonModal, setShowLessonModal] = useState(null);
    const [studentListMode, setStudentListMode] = useState('available');
    const [lessonListMode, setLessonListMode] = useState('available');
    const [pendingMove, setPendingMove] = useState(null);
    const [pendingDelete, setPendingDelete] = useState(null);
    const [viewingLesson, setViewingLesson] = useState(null);

    const courseForm = useForm({ discipline: activeTab, name: '', view: activeView });
    const courseEditForm = useForm({
        name: '',
        study_starts_at: '08:00',
        study_ends_at: '13:00',
        view: activeView,
    });
    const groupForm = useForm({ name: '', color: DEFAULT_GROUP_COLOR, view: activeView });
    const groupEditForm = useForm({ name: '', color: DEFAULT_GROUP_COLOR, view: activeView });
    const studentForm = useForm({ student_ids: [], confirm_move: false });
    const lessonForm = useForm({ items: [] });
    const lessonEditForm = useForm({ assignments: [], duration_minutes: '' });

    useEffect(() => {
        if (!viewingLesson) {
            return;
        }

        lessonEditForm.setData({
            assignments: (viewingLesson.assignments ?? []).map((assignment) => ({
                teacher_id: assignment.teacher_id,
                hours: String(assignment.hours ?? ''),
            })),
            duration_minutes: viewingLesson.duration_minutes != null
                ? String(viewingLesson.duration_minutes)
                : '',
        });
        lessonEditForm.clearErrors();
    }, [viewingLesson?.lessonId, viewingLesson?.groupId]);

    const switchView = (nextView) => {
        if (nextView === activeView) {
            return;
        }

        saveExpandedState(activeTab, activeView, expandedCourses, expandedGroups);

        router.get(route('courses-groups.index'), { tab: activeTab, view: nextView }, {
            preserveState: false,
            preserveScroll: false,
            replace: true,
        });
    };

    const switchTab = (nextTab) => {
        if (nextTab === activeTab) {
            return;
        }

        saveExpandedState(activeTab, activeView, expandedCourses, expandedGroups);

        router.get(route('courses-groups.index'), { tab: nextTab, view: activeView }, {
            preserveState: false,
            preserveScroll: false,
            replace: true,
        });
    };

    const toggleCourse = (courseId) => {
        setExpandedCourses((prev) => {
            const next = new Set(prev);
            if (next.has(courseId)) {
                next.delete(courseId);
            } else {
                next.add(courseId);
            }
            return next;
        });
    };

    const toggleGroup = (groupId) => {
        setExpandedGroups((prev) => {
            const next = new Set(prev);
            if (next.has(groupId)) {
                next.delete(groupId);
            } else {
                next.add(groupId);
            }
            return next;
        });
    };

    const openCourseModal = () => {
        courseForm.setData({ discipline: activeTab, name: '', view: activeView });
        courseForm.clearErrors();
        setShowCourseModal(true);
    };

    const openCourseSettings = (course) => {
        courseEditForm.setData({
            name: course.name ?? '',
            study_starts_at: course.study_starts_at || '08:00',
            study_ends_at: course.study_ends_at || '13:00',
            view: activeView,
        });
        courseEditForm.clearErrors();
        setShowCourseSettingsModal(course);
    };

    const submitCourse = (e) => {
        e.preventDefault();
        courseForm.post(route('courses-groups.courses.store'), {
            preserveScroll: true,
            onSuccess: () => setShowCourseModal(false),
        });
    };

    const submitCourseSettings = (e) => {
        e.preventDefault();
        if (!showCourseSettingsModal) {
            return;
        }

        courseEditForm.patch(route('courses-groups.courses.update', showCourseSettingsModal.id), {
            preserveScroll: true,
            onSuccess: () => setShowCourseSettingsModal(null),
        });
    };

    const submitGroup = (e) => {
        e.preventDefault();
        if (!showGroupModal) return;

        groupForm.post(route('courses-groups.groups.store', showGroupModal), {
            preserveScroll: true,
            onSuccess: () => {
                setShowGroupModal(null);
                setExpandedCourses((prev) => new Set(prev).add(showGroupModal));
            },
        });
    };

    const openGroupSettings = (group) => {
        groupEditForm.setData({
            name: group.name ?? '',
            color: group.color || DEFAULT_GROUP_COLOR,
            view: activeView,
        });
        groupEditForm.clearErrors();
        setShowGroupSettingsModal(group);
    };

    const submitGroupSettings = (e) => {
        e.preventDefault();
        if (!showGroupSettingsModal) return;

        groupEditForm.patch(route('courses-groups.groups.update', showGroupSettingsModal.id), {
            preserveScroll: true,
            onSuccess: () => setShowGroupSettingsModal(null),
        });
    };

    const submitStudent = (e) => {
        e.preventDefault();
        if (!showStudentModal) return;

        const selectedIds = studentForm.data.student_ids;
        const occupiedStudents = selectedIds
            .map((id) => {
                const assignment = studentAssignments[id];
                if (!assignment || assignment.group_id === showStudentModal) {
                    return null;
                }

                const student = students.find((item) => item.id === id);
                return student ? { ...student, assignment } : null;
            })
            .filter(Boolean);

        if (occupiedStudents.length > 0) {
            setPendingMove({ students: occupiedStudents });
            return;
        }

        studentForm.transform((data) => ({ ...data, confirm_move: false }));
        studentForm.post(route('courses-groups.students.attach', showStudentModal), {
            preserveScroll: true,
            onSuccess: () => {
                setShowStudentModal(null);
                setStudentListMode('available');
                setPendingMove(null);
                setExpandedGroups((prev) => new Set(prev).add(showStudentModal));
            },
        });
    };

    const confirmMoveStudents = () => {
        if (!showStudentModal) return;

        studentForm.transform((data) => ({ ...data, confirm_move: true }));
        studentForm.post(route('courses-groups.students.attach', showStudentModal), {
            preserveScroll: true,
            onSuccess: () => {
                setShowStudentModal(null);
                setStudentListMode('available');
                setPendingMove(null);
                setExpandedGroups((prev) => new Set(prev).add(showStudentModal));
            },
        });
    };

    const openDeleteCourse = (course) => {
        setPendingDelete({ type: 'course', courseId: course.id, label: course.name });
    };

    const openDeleteGroup = (group) => {
        setPendingDelete({ type: 'group', groupId: group.id, label: group.name });
    };

    const openRemoveStudent = (groupId, student) => {
        setPendingDelete({
            type: 'student',
            groupId,
            studentId: student.id,
            label: student.name,
        });
    };

    const openLessonDetails = (lesson, group, course) => {
        const catalogEntry = catalogLessons.find((entry) => entry.id === lesson.id);
        const assignments = lesson.assignments?.length
            ? lesson.assignments
            : [{
                teacher_id: lesson.teacher_id,
                teacher_name: lesson.teacher_name,
                hours: lesson.hours,
            }];

        setViewingLesson({
            lessonId: lesson.id,
            groupId: group.id,
            name: lesson.name,
            description: lesson.description,
            duration_minutes: lesson.duration_minutes ?? catalogEntry?.duration_minutes ?? null,
            assignments,
            availableTeachers: catalogEntry?.teachers ?? [],
            groupName: group.name,
            courseName: course.name,
        });
    };

    const addAssignmentTeacher = (teacherId) => {
        const id = Number(teacherId);
        if (!id) {
            return;
        }

        if ((lessonEditForm.data.assignments ?? []).some((assignment) => assignment.teacher_id === id)) {
            return;
        }

        lessonEditForm.setData('assignments', [
            ...(lessonEditForm.data.assignments ?? []),
            { teacher_id: id, hours: '' },
        ]);
    };

    const removeAssignmentTeacher = (teacherId) => {
        lessonEditForm.setData(
            'assignments',
            (lessonEditForm.data.assignments ?? []).filter((assignment) => assignment.teacher_id !== teacherId),
        );
    };

    const updateAssignmentHours = (teacherId, hours) => {
        lessonEditForm.setData(
            'assignments',
            (lessonEditForm.data.assignments ?? []).map((assignment) => (
                assignment.teacher_id === teacherId
                    ? { ...assignment, hours }
                    : assignment
            )),
        );
    };

    const submitLessonEdit = (e) => {
        e.preventDefault();
        if (!viewingLesson) {
            return;
        }

        lessonEditForm.transform((data) => ({
            duration_minutes: Number(data.duration_minutes),
            assignments: (data.assignments ?? []).map((assignment) => ({
                teacher_id: Number(assignment.teacher_id),
                hours: Number(assignment.hours),
            })),
        }));

        lessonEditForm.patch(
            route('courses-groups.lessons.update', [viewingLesson.groupId, viewingLesson.lessonId]),
            {
                preserveScroll: true,
                onSuccess: () => setViewingLesson(null),
            },
        );
    };

    const lessonEditValid = (lessonEditForm.data.assignments ?? []).length > 0
        && (lessonEditForm.data.assignments ?? []).every(
            (assignment) => assignment.teacher_id && assignment.hours && Number(assignment.hours) > 0,
        )
        && lessonEditForm.data.duration_minutes
        && Number(lessonEditForm.data.duration_minutes) >= 15;

    const openRemoveLesson = (groupId, lesson) => {
        setPendingDelete({
            type: 'lesson',
            groupId,
            lessonId: lesson.id,
            label: lesson.name,
        });
    };

    const confirmPendingDelete = () => {
        if (!pendingDelete) {
            return;
        }

        const options = {
            preserveScroll: true,
            onFinish: () => setPendingDelete(null),
        };

        if (pendingDelete.type === 'course') {
            router.delete(route('courses-groups.courses.destroy', pendingDelete.courseId), options);
            return;
        }

        if (pendingDelete.type === 'group') {
            router.delete(route('courses-groups.groups.destroy', pendingDelete.groupId), options);
            return;
        }

        if (pendingDelete.type === 'lesson') {
            router.delete(
                route('courses-groups.lessons.detach', [pendingDelete.groupId, pendingDelete.lessonId]),
                options,
            );
            return;
        }

        router.delete(
            route('courses-groups.students.detach', [pendingDelete.groupId, pendingDelete.studentId]),
            options,
        );
    };

    const pendingDeleteMessage = pendingDelete?.type === 'course'
        ? t.confirmDeleteCourse
        : pendingDelete?.type === 'group'
            ? t.confirmDeleteGroup
            : pendingDelete?.type === 'lesson'
                ? t.confirmRemoveLesson
                : t.confirmRemoveStudent;

    const toggleLessonSelection = (lesson) => {
        const items = lessonForm.data.items ?? [];
        const exists = items.find((item) => item.lesson_id === lesson.id);

        if (exists) {
            lessonForm.setData(
                'items',
                items.filter((item) => item.lesson_id !== lesson.id),
            );
            return;
        }

        const defaultTeacherId = lesson.teachers?.[0]?.id ?? '';
        lessonForm.setData('items', [
            ...items,
            {
                lesson_id: lesson.id,
                teacher_id: defaultTeacherId,
                hours: '',
            },
        ]);
    };

    const updateLessonItem = (lessonId, field, value) => {
        lessonForm.setData(
            'items',
            (lessonForm.data.items ?? []).map((item) => (
                item.lesson_id === lessonId ? { ...item, [field]: value } : item
            )),
        );
    };

    const submitLesson = (e) => {
        e.preventDefault();
        if (!showLessonModal) return;

        lessonForm.post(route('courses-groups.lessons.attach', showLessonModal), {
            preserveScroll: true,
            onSuccess: () => {
                setShowLessonModal(null);
                setLessonListMode('available');
                setExpandedGroups((prev) => new Set(prev).add(showLessonModal));
            },
        });
    };

    const lessonModalContext = useMemo(() => {
        if (!showLessonModal) {
            return { lessons: [], assignedInCurrentGroup: new Set() };
        }

        const activeGroup = courses
            .flatMap((course) => course.groups ?? [])
            .find((group) => group.id === showLessonModal);

        const assignedInCurrentGroup = new Set(
            (activeGroup?.lessons ?? []).map((lesson) => lesson.id),
        );

        const lessons = lessonListMode === 'all'
            ? catalogLessons
            : catalogLessons.filter((lesson) => !assignedInCurrentGroup.has(lesson.id));

        return { lessons, assignedInCurrentGroup };
    }, [courses, showLessonModal, catalogLessons, lessonListMode]);

    const selectableLessonsForGroup = lessonModalContext.lessons;
    const assignedInCurrentGroup = lessonModalContext.assignedInCurrentGroup;

    const emptyLessonsMessage = lessonListMode === 'available'
        ? t.noFreeLessons
        : t.noLessonsAvailable;

    const lessonSelectionValid = (lessonForm.data.items ?? []).every(
        (item) => item.teacher_id && item.hours && Number(item.hours) > 0,
    );

    const toggleStudentSelection = (studentId) => {
        const ids = studentForm.data.student_ids;
        if (ids.includes(studentId)) {
            studentForm.setData('student_ids', ids.filter((id) => id !== studentId));
        } else {
            studentForm.setData('student_ids', [...ids, studentId]);
        }
    };

    const selectAllStudents = () => {
        studentForm.setData(
            'student_ids',
            selectableStudentsForGroup.map((student) => student.id),
        );
    };

    const deselectAllStudents = () => {
        studentForm.setData('student_ids', []);
    };

    const selectableStudentsForGroup = useMemo(() => {
        if (!showStudentModal) return [];

        const activeGroup = courses
            .flatMap((course) => course.groups ?? [])
            .find((group) => group.id === showStudentModal);

        if (!activeGroup) return students;

        const assignedInCurrentGroup = new Set((activeGroup.students ?? []).map((s) => s.id));

        return students.filter((student) => {
            if (assignedInCurrentGroup.has(student.id)) {
                return false;
            }

            if (studentListMode === 'available') {
                return !studentAssignments[student.id];
            }

            return true;
        });
    }, [courses, showStudentModal, students, studentListMode, studentAssignments]);

    const emptyStudentsMessage = studentListMode === 'available'
        ? t.noFreeStudents
        : t.noStudentsAvailable;

    const tabLabels = { academy: t.tabAcademy, tam: t.tabTam, carcano: t.tabCarcano };

    const viewToggle = (
        <nav
            className="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 shadow-sm"
            role="tablist"
        >
            <button
                type="button"
                role="tab"
                aria-selected={!isLessonsView}
                onClick={() => switchView('students')}
                className={`min-w-[6.5rem] rounded-lg px-4 py-2 text-sm font-semibold transition sm:min-w-[7rem] sm:px-5 sm:py-2.5 ${
                    !isLessonsView
                        ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                        : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
                }`}
            >
                {t.viewStudents}
            </button>
            <button
                type="button"
                role="tab"
                aria-selected={isLessonsView}
                onClick={() => switchView('lessons')}
                className={`min-w-[6.5rem] rounded-lg px-4 py-2 text-sm font-semibold transition sm:min-w-[7rem] sm:px-5 sm:py-2.5 ${
                    isLessonsView
                        ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                        : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
                }`}
            >
                {t.viewLessons}
            </button>
        </nav>
    );

    return (
        <AdminLayout title={t.pageTitle} headerCenter={viewToggle}>
            <Head title={t.pageTitle} />

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
                                            : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
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
                            onClick={openCourseModal}
                            className="inline-flex h-10 items-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700"
                        >
                            <Plus className="h-4 w-4" />
                            {t.addCourse}
                        </button>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    {courses.length === 0 ? (
                        <div className="px-6 py-12 text-center text-sm text-slate-500">
                            {t.emptyCourses}
                        </div>
                    ) : (
                        <div className="divide-y divide-slate-100">
                            {courses.map((course) => {
                                const courseExpanded = expandedCourses.has(course.id);
                                const groupCount = course.groups?.length ?? 0;

                                return (
                                    <div key={course.id}>
                                        <div className="flex items-center gap-2 px-3 py-2.5 hover:bg-slate-50">
                                            <button
                                                type="button"
                                                onClick={() => toggleCourse(course.id)}
                                                className="rounded p-1 text-slate-500 hover:bg-slate-100"
                                            >
                                                {courseExpanded
                                                    ? <ChevronDown className="h-4 w-4" />
                                                    : <ChevronRight className="h-4 w-4" />}
                                            </button>
                                            {courseExpanded
                                                ? <FolderOpen className="h-4 w-4 shrink-0 text-amber-500" />
                                                : <Folder className="h-4 w-4 shrink-0 text-amber-500" />}
                                            <span className="min-w-0 flex-1 truncate font-medium text-slate-900">
                                                {course.name}
                                            </span>
                                            {(course.study_starts_at && course.study_ends_at) && (
                                                <span className="hidden text-xs text-slate-500 sm:inline">
                                                    {course.study_starts_at}–{course.study_ends_at}
                                                </span>
                                            )}
                                            <span className="text-xs text-slate-500">
                                                {groupCount} {t.groupsCount}
                                            </span>
                                            {canWrite && (
                                                <button
                                                    type="button"
                                                    title={t.courseSettings}
                                                    onClick={() => openCourseSettings(course)}
                                                    className="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                                >
                                                    <Settings className="h-4 w-4" />
                                                </button>
                                            )}
                                            {canWrite && (
                                                <button
                                                    type="button"
                                                    title={t.addGroup}
                                                    onClick={() => {
                                                        groupForm.setData({ name: '', color: DEFAULT_GROUP_COLOR, view: activeView });
                                                        groupForm.clearErrors();
                                                        setShowGroupModal(course.id);
                                                    }}
                                                    className="rounded-lg p-1.5 text-indigo-600 transition hover:bg-indigo-50"
                                                >
                                                    <Plus className="h-4 w-4" />
                                                </button>
                                            )}
                                            {canDelete && (
                                                <button
                                                    type="button"
                                                    title={t.deleteCourse}
                                                    onClick={() => openDeleteCourse(course)}
                                                    className="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            )}
                                        </div>

                                        {courseExpanded && (course.groups ?? []).map((group) => {
                                            const groupExpanded = expandedGroups.has(group.id);
                                            const itemCount = isLessonsView
                                                ? (group.lessons?.length ?? 0)
                                                : (group.students?.length ?? 0);
                                            const itemCountLabel = isLessonsView ? t.lessonsCount : t.studentsCount;

                                            return (
                                                <div key={group.id}>
                                                    <div className="flex items-center gap-2 border-t border-slate-50 py-2.5 pl-10 pr-3 hover:bg-slate-50">
                                                        <button
                                                            type="button"
                                                            onClick={() => toggleGroup(group.id)}
                                                            className="rounded p-1 text-slate-500 hover:bg-slate-100"
                                                        >
                                                            {groupExpanded
                                                                ? <ChevronDown className="h-4 w-4" />
                                                                : <ChevronRight className="h-4 w-4" />}
                                                        </button>
                                                        {groupExpanded
                                                            ? <FolderOpen className="h-4 w-4 shrink-0 text-sky-500" />
                                                            : <Folder className="h-4 w-4 shrink-0 text-sky-500" />}
                                                        <span
                                                            className="h-3 w-3 shrink-0 rounded-full border border-slate-200"
                                                            style={{ backgroundColor: group.color || DEFAULT_GROUP_COLOR }}
                                                        />
                                                        <span className="min-w-0 flex-1 truncate text-slate-800">
                                                            {group.name}
                                                        </span>
                                                        <span className="text-xs text-slate-500">
                                                            {itemCount} {itemCountLabel}
                                                        </span>
                                                        {canWrite && (
                                                            <>
                                                                <button
                                                                    type="button"
                                                                    title={t.groupSettings}
                                                                    onClick={() => openGroupSettings(group)}
                                                                    className="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                                                >
                                                                    <Settings className="h-4 w-4" />
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    title={isLessonsView ? t.addLesson : t.addStudent}
                                                                    onClick={() => {
                                                                    if (isLessonsView) {
                                                                        lessonForm.setData({ items: [] });
                                                                        lessonForm.clearErrors();
                                                                        setLessonListMode('available');
                                                                        setShowLessonModal(group.id);
                                                                        return;
                                                                    }

                                                                    studentForm.setData({ student_ids: [], confirm_move: false });
                                                                    studentForm.clearErrors();
                                                                    setStudentListMode('available');
                                                                    setPendingMove(null);
                                                                    setShowStudentModal(group.id);
                                                                }}
                                                                className="rounded-lg p-1.5 text-indigo-600 transition hover:bg-indigo-50"
                                                            >
                                                                <Plus className="h-4 w-4" />
                                                            </button>
                                                            </>
                                                        )}
                                                        {canDelete && (
                                                            <button
                                                                type="button"
                                                                title={t.deleteGroup}
                                                                onClick={() => openDeleteGroup(group)}
                                                                className="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        )}
                                                    </div>

                                                    {groupExpanded && isLessonsView && (group.lessons ?? []).map((lesson) => (
                                                        <div
                                                            key={lesson.id}
                                                            className="flex cursor-pointer items-center gap-2 border-t border-slate-50 py-2 pl-20 pr-3 hover:bg-slate-50"
                                                            onClick={() => openLessonDetails(lesson, group, course)}
                                                        >
                                                            <div className="min-w-0 flex-1">
                                                                <div className="truncate text-sm font-medium text-slate-800">
                                                                    {lesson.name}
                                                                </div>
                                                                <div className="truncate text-xs text-slate-500">
                                                                    {formatLessonAssignmentsSummary(lesson, t)}
                                                                </div>
                                                                {lesson.description && (
                                                                    <div className="truncate text-xs text-slate-400">
                                                                        {lesson.description}
                                                                    </div>
                                                                )}
                                                            </div>
                                                            {canDelete && (
                                                                <button
                                                                    type="button"
                                                                    title={t.removeLesson}
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        openRemoveLesson(group.id, lesson);
                                                                    }}
                                                                    className="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    ))}

                                                    {groupExpanded && !isLessonsView && (group.students ?? []).map((student) => (
                                                        <div
                                                            key={student.id}
                                                            className="flex cursor-pointer items-center gap-2 border-t border-slate-50 py-2 pl-20 pr-3 hover:bg-slate-50"
                                                            onClick={() => {
                                                                saveExpandedState(
                                                                    activeTab,
                                                                    activeView,
                                                                    expandedCourses,
                                                                    expandedGroups,
                                                                );
                                                                router.get(route('customers.show', student.id), {
                                                                    from: 'courses-groups',
                                                                    tab: activeTab,
                                                                    view: activeView,
                                                                });
                                                            }}
                                                        >
                                                            <StudentAvatar student={student} />
                                                            <div className="min-w-0 flex-1">
                                                                <div className="truncate text-sm text-slate-800">
                                                                    {student.name}
                                                                </div>
                                                                {student.email && (
                                                                    <div className="truncate text-xs text-slate-500">
                                                                        {student.email}
                                                                    </div>
                                                                )}
                                                            </div>
                                                            {canDelete && (
                                                                <button
                                                                    type="button"
                                                                    title={t.removeStudent}
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        openRemoveStudent(group.id, student);
                                                                    }}
                                                                    className="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            );
                                        })}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>

            {showCourseModal && (
                <Modal title={t.addCourse} onClose={() => setShowCourseModal(false)}>
                    <form onSubmit={submitCourse} className="space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                {t.courseName}
                            </label>
                            <input
                                type="text"
                                value={courseForm.data.name}
                                onChange={(e) => courseForm.setData('name', e.target.value)}
                                className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                                autoFocus
                                required
                            />
                            {courseForm.errors.name && (
                                <p className="mt-1 text-sm text-red-600">{courseForm.errors.name}</p>
                            )}
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowCourseModal(false)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={courseForm.processing}
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.save}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {showCourseSettingsModal && (
                <Modal title={t.courseSettings} onClose={() => setShowCourseSettingsModal(null)}>
                    <form onSubmit={submitCourseSettings} className="space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                {t.courseName}
                            </label>
                            <input
                                type="text"
                                value={courseEditForm.data.name}
                                onChange={(e) => courseEditForm.setData('name', e.target.value)}
                                className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                                required
                            />
                            {courseEditForm.errors.name && (
                                <p className="mt-1 text-sm text-red-600">{courseEditForm.errors.name}</p>
                            )}
                        </div>
                        <div>
                            <p className="mb-1 text-sm font-medium text-slate-700">{t.studyWindow}</p>
                            <p className="mb-2 text-xs text-slate-500">{t.studyWindowHint}</p>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="mb-1 block text-xs font-medium text-slate-600">
                                        {t.studyStartsAt}
                                    </label>
                                    <input
                                        type="time"
                                        step="1800"
                                        value={courseEditForm.data.study_starts_at}
                                        onChange={(e) => courseEditForm.setData('study_starts_at', e.target.value)}
                                        className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                                        required
                                    />
                                    {courseEditForm.errors.study_starts_at && (
                                        <p className="mt-1 text-sm text-red-600">{courseEditForm.errors.study_starts_at}</p>
                                    )}
                                </div>
                                <div>
                                    <label className="mb-1 block text-xs font-medium text-slate-600">
                                        {t.studyEndsAt}
                                    </label>
                                    <input
                                        type="time"
                                        step="1800"
                                        value={courseEditForm.data.study_ends_at}
                                        onChange={(e) => courseEditForm.setData('study_ends_at', e.target.value)}
                                        className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                                        required
                                    />
                                    {courseEditForm.errors.study_ends_at && (
                                        <p className="mt-1 text-sm text-red-600">{courseEditForm.errors.study_ends_at}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowCourseSettingsModal(null)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={courseEditForm.processing}
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.save}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {showGroupModal && (
                <Modal title={t.addGroup} onClose={() => setShowGroupModal(null)}>
                    <form onSubmit={submitGroup} className="space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                {t.groupName}
                            </label>
                            <input
                                type="text"
                                value={groupForm.data.name}
                                onChange={(e) => groupForm.setData('name', e.target.value)}
                                className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                                autoFocus
                                required
                            />
                            {groupForm.errors.name && (
                                <p className="mt-1 text-sm text-red-600">{groupForm.errors.name}</p>
                            )}
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                {t.groupColor}
                            </label>
                            <input
                                type="color"
                                value={groupForm.data.color || DEFAULT_GROUP_COLOR}
                                onChange={(e) => groupForm.setData('color', e.target.value)}
                                className="h-10 w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                            />
                            {groupForm.errors.color && (
                                <p className="mt-1 text-sm text-red-600">{groupForm.errors.color}</p>
                            )}
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowGroupModal(null)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={groupForm.processing}
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.save}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {showGroupSettingsModal && (
                <Modal title={t.groupSettings} onClose={() => setShowGroupSettingsModal(null)}>
                    <form onSubmit={submitGroupSettings} className="space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                {t.groupName}
                            </label>
                            <input
                                type="text"
                                value={groupEditForm.data.name}
                                onChange={(e) => groupEditForm.setData('name', e.target.value)}
                                className="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm"
                                autoFocus
                                required
                            />
                            {groupEditForm.errors.name && (
                                <p className="mt-1 text-sm text-red-600">{groupEditForm.errors.name}</p>
                            )}
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium text-slate-700">
                                {t.groupColor}
                            </label>
                            <input
                                type="color"
                                value={groupEditForm.data.color || DEFAULT_GROUP_COLOR}
                                onChange={(e) => groupEditForm.setData('color', e.target.value)}
                                className="h-10 w-full cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                            />
                            {groupEditForm.errors.color && (
                                <p className="mt-1 text-sm text-red-600">{groupEditForm.errors.color}</p>
                            )}
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setShowGroupSettingsModal(null)}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={groupEditForm.processing}
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.save}
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {showStudentModal && (
                <Modal title={t.addStudent} onClose={() => {
                    setShowStudentModal(null);
                    setStudentListMode('available');
                    setPendingMove(null);
                }}>
                    <form onSubmit={submitStudent} className="space-y-4">
                        <div>
                            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <label className="text-sm font-medium text-slate-700">
                                    {t.selectStudents}
                                </label>
                                <nav
                                    className="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1"
                                    role="tablist"
                                >
                                    <button
                                        type="button"
                                        role="tab"
                                        aria-selected={studentListMode === 'available'}
                                        onClick={() => {
                                            setStudentListMode('available');
                                            studentForm.setData(
                                                'student_ids',
                                                studentForm.data.student_ids.filter(
                                                    (id) => !studentAssignments[id],
                                                ),
                                            );
                                        }}
                                        className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${
                                            studentListMode === 'available'
                                                ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                                                : 'text-slate-600 hover:text-slate-900'
                                        }`}
                                    >
                                        {t.filterAvailable}
                                    </button>
                                    <button
                                        type="button"
                                        role="tab"
                                        aria-selected={studentListMode === 'all'}
                                        onClick={() => setStudentListMode('all')}
                                        className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${
                                            studentListMode === 'all'
                                                ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                                                : 'text-slate-600 hover:text-slate-900'
                                        }`}
                                    >
                                        {t.filterAll}
                                    </button>
                                </nav>
                            </div>
                            {selectableStudentsForGroup.length > 0 && (
                                <div className="mb-2 flex items-center justify-end gap-2 text-xs">
                                    <span className="text-slate-500">
                                        {t.selectedCount}: {studentForm.data.student_ids.length}
                                    </span>
                                    <button
                                        type="button"
                                        onClick={selectAllStudents}
                                        className="font-medium text-indigo-600 hover:text-indigo-700"
                                    >
                                        {t.selectAll}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={deselectAllStudents}
                                        className="font-medium text-slate-500 hover:text-slate-700"
                                    >
                                        {t.deselectAll}
                                    </button>
                                </div>
                            )}
                            {selectableStudentsForGroup.length === 0 ? (
                                <p className="text-sm text-slate-500">{emptyStudentsMessage}</p>
                            ) : (
                                <div className="max-h-72 overflow-y-auto rounded-lg border border-slate-200 divide-y divide-slate-100">
                                    {selectableStudentsForGroup.map((student) => {
                                        const checked = studentForm.data.student_ids.includes(student.id);
                                        const assignment = studentAssignments[student.id];

                                        return (
                                            <label
                                                key={student.id}
                                                className={`flex cursor-pointer items-start gap-3 px-3 py-2.5 transition hover:bg-slate-50 ${
                                                    checked ? 'bg-indigo-50/60' : ''
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={() => toggleStudentSelection(student.id)}
                                                    className="mt-1.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <StudentAvatar student={student} size="md" />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm font-medium text-slate-800">
                                                        {student.name}
                                                    </span>
                                                    {assignment && (
                                                        <span className="block text-xs text-amber-700">
                                                            {formatAssignment(t, assignment)}
                                                        </span>
                                                    )}
                                                    {student.email && (
                                                        <span className="block truncate text-xs text-slate-500">
                                                            {student.email}
                                                        </span>
                                                    )}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                            )}
                            {studentForm.errors.student_ids && (
                                <p className="mt-1 text-sm text-red-600">{studentForm.errors.student_ids}</p>
                            )}
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setShowStudentModal(null);
                                    setStudentListMode('available');
                                    setPendingMove(null);
                                }}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={
                                    studentForm.processing
                                    || selectableStudentsForGroup.length === 0
                                    || studentForm.data.student_ids.length === 0
                                }
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.add} ({studentForm.data.student_ids.length})
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {showLessonModal && (
                <Modal
                    title={t.addLesson}
                    wide
                    onClose={() => {
                        setShowLessonModal(null);
                        setLessonListMode('available');
                        setPendingLessonMove(null);
                    }}
                >
                    <form onSubmit={submitLesson} className="space-y-4">
                        <div>
                            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <label className="text-sm font-medium text-slate-700">
                                    {t.selectLessons}
                                </label>
                                <nav
                                    className="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-1"
                                    role="tablist"
                                >
                                    <button
                                        type="button"
                                        role="tab"
                                        aria-selected={lessonListMode === 'available'}
                                        onClick={() => {
                                            setLessonListMode('available');
                                            lessonForm.setData(
                                                'items',
                                                (lessonForm.data.items ?? []).filter(
                                                    (item) => !assignedInCurrentGroup.has(item.lesson_id),
                                                ),
                                            );
                                        }}
                                        className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${
                                            lessonListMode === 'available'
                                                ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                                                : 'text-slate-600 hover:text-slate-900'
                                        }`}
                                    >
                                        {t.filterAvailable}
                                    </button>
                                    <button
                                        type="button"
                                        role="tab"
                                        aria-selected={lessonListMode === 'all'}
                                        onClick={() => setLessonListMode('all')}
                                        className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${
                                            lessonListMode === 'all'
                                                ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200'
                                                : 'text-slate-600 hover:text-slate-900'
                                        }`}
                                    >
                                        {t.filterAll}
                                    </button>
                                </nav>
                            </div>
                            {selectableLessonsForGroup.length === 0 ? (
                                <p className="text-sm text-slate-500">{emptyLessonsMessage}</p>
                            ) : (
                                <div className="max-h-80 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-2">
                                    {selectableLessonsForGroup.map((lesson) => {
                                        const selectedItem = (lessonForm.data.items ?? []).find(
                                            (item) => item.lesson_id === lesson.id,
                                        );
                                        const inCurrentGroup = assignedInCurrentGroup.has(lesson.id);
                                        const checked = !!selectedItem && !inCurrentGroup;
                                        const elsewhereAssignments = lessonAssignmentsElsewhere(
                                            lessonAssignments,
                                            lesson.id,
                                            showLessonModal,
                                        );

                                        return (
                                            <div
                                                key={lesson.id}
                                                className={`rounded-lg border px-3 py-2.5 transition ${
                                                    inCurrentGroup
                                                        ? 'border-slate-100 bg-slate-50 opacity-70'
                                                        : checked
                                                            ? 'border-indigo-200 bg-indigo-50/60'
                                                            : 'border-slate-100 hover:bg-slate-50'
                                                }`}
                                            >
                                                <label className={`flex items-start gap-3 ${inCurrentGroup ? 'cursor-not-allowed' : 'cursor-pointer'}`}>
                                                    <input
                                                        type="checkbox"
                                                        checked={checked}
                                                        disabled={inCurrentGroup}
                                                        onChange={() => toggleLessonSelection(lesson)}
                                                        className="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed"
                                                    />
                                                    <span className="min-w-0 flex-1">
                                                        <span className="block text-sm font-medium text-slate-800">
                                                            {lesson.name}
                                                        </span>
                                                        {inCurrentGroup && (
                                                            <span className="block text-xs text-slate-500">
                                                                {t.alreadyInThisGroup}
                                                            </span>
                                                        )}
                                                        {elsewhereAssignments.map((assignment) => (
                                                            <span
                                                                key={`${assignment.group_id}-${assignment.course_name}`}
                                                                className="block text-xs text-amber-700"
                                                            >
                                                                {formatAssignment(t, assignment)}
                                                            </span>
                                                        ))}
                                                        {lesson.description && (
                                                            <span className="block text-xs text-slate-500">
                                                                {lesson.description}
                                                            </span>
                                                        )}
                                                    </span>
                                                </label>
                                                {checked && (
                                                    <div className="mt-3 grid gap-2 pl-7 sm:grid-cols-2">
                                                        <div>
                                                            <label className="mb-1 block text-xs font-medium text-slate-600">
                                                                {t.teacher}
                                                            </label>
                                                            <select
                                                                value={selectedItem.teacher_id}
                                                                onChange={(e) => updateLessonItem(
                                                                    lesson.id,
                                                                    'teacher_id',
                                                                    e.target.value ? Number(e.target.value) : '',
                                                                )}
                                                                className="h-9 w-full rounded-lg border border-slate-300 px-2 text-sm"
                                                                required
                                                            >
                                                                <option value="">{t.chooseTeacher}</option>
                                                                {(lesson.teachers ?? []).map((teacher) => (
                                                                    <option key={teacher.id} value={teacher.id}>
                                                                        {teacher.name}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label className="mb-1 block text-xs font-medium text-slate-600">
                                                                {t.hoursPerWeek}
                                                            </label>
                                                            <input
                                                                type="number"
                                                                min="1"
                                                                max="999"
                                                                placeholder={t.hoursPlaceholder}
                                                                value={selectedItem.hours}
                                                                onChange={(e) => updateLessonItem(
                                                                    lesson.id,
                                                                    'hours',
                                                                    e.target.value,
                                                                )}
                                                                className="h-9 w-full rounded-lg border border-slate-300 px-2 text-sm"
                                                                required
                                                            />
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                            {lessonForm.errors.items && (
                                <p className="mt-1 text-sm text-red-600">{lessonForm.errors.items}</p>
                            )}
                        </div>
                        <div className="flex justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setShowLessonModal(null);
                                    setLessonListMode('available');
                                    setPendingLessonMove(null);
                                }}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.cancel}
                            </button>
                            <button
                                type="submit"
                                disabled={
                                    lessonForm.processing
                                    || selectableLessonsForGroup.length === 0
                                    || (lessonForm.data.items ?? []).length === 0
                                    || !lessonSelectionValid
                                }
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            >
                                {t.add} ({(lessonForm.data.items ?? []).length})
                            </button>
                        </div>
                    </form>
                </Modal>
            )}

            {pendingMove && (
                <Modal title={t.confirmMoveTitle} onClose={() => setPendingMove(null)}>
                    <p className="text-sm text-slate-600">{t.confirmMoveMessage}</p>
                    <ul className="mt-3 max-h-48 space-y-2 overflow-y-auto">
                        {pendingMove.students.map((student) => (
                            <li
                                key={student.id}
                                className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900"
                            >
                                <span className="font-medium">{student.name}</span>
                                {student.assignment && (
                                    <span className="mt-0.5 block text-xs">
                                        {formatAssignment(t, student.assignment)}
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                    <div className="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setPendingMove(null)}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                        >
                            {t.cancel}
                        </button>
                        <button
                            type="button"
                            onClick={confirmMoveStudents}
                            disabled={studentForm.processing}
                            className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60"
                        >
                            {t.confirmMove}
                        </button>
                    </div>
                </Modal>
            )}

            {viewingLesson && (
                <Modal
                    title={t.lessonDetails}
                    wide
                    onClose={() => {
                        setViewingLesson(null);
                        lessonEditForm.reset();
                        lessonEditForm.clearErrors();
                    }}
                >
                    <form onSubmit={submitLessonEdit} className="space-y-4 text-sm">
                        <dl className="space-y-3">
                            <div>
                                <dt className="mb-1 font-medium text-slate-500">{t.courseName}</dt>
                                <dd className="text-slate-900">{viewingLesson.courseName}</dd>
                            </div>
                            <div>
                                <dt className="mb-1 font-medium text-slate-500">{t.groupName}</dt>
                                <dd className="text-slate-900">{viewingLesson.groupName}</dd>
                            </div>
                            <div>
                                <dt className="mb-1 font-medium text-slate-500">{t.lessonName}</dt>
                                <dd className="font-semibold text-slate-900">{viewingLesson.name}</dd>
                            </div>
                            <div>
                                <dt className="mb-1 font-medium text-slate-500">{t.description}</dt>
                                <dd className="whitespace-pre-wrap text-slate-800">
                                    {viewingLesson.description?.trim() ? viewingLesson.description : t.noDescription}
                                </dd>
                            </div>
                        </dl>

                        <div>
                            <label className="mb-1 block font-medium text-slate-700">
                                {t.lessonDuration}
                            </label>
                            <div className="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="15"
                                    max="480"
                                    step="5"
                                    value={lessonEditForm.data.duration_minutes}
                                    onChange={(e) => lessonEditForm.setData('duration_minutes', e.target.value)}
                                    disabled={!canWrite}
                                    placeholder="60"
                                    className="h-10 w-28 rounded-lg border border-slate-300 px-3 text-sm disabled:bg-slate-100"
                                    required
                                />
                                <span className="text-sm text-slate-600">{t.minutesShort}</span>
                            </div>
                            {lessonEditForm.errors.duration_minutes && (
                                <p className="mt-1 text-sm text-red-600">{lessonEditForm.errors.duration_minutes}</p>
                            )}
                        </div>

                        <div>
                            <div className="mb-2 flex items-center justify-between gap-2">
                                <label className="font-medium text-slate-700">{t.assignedTeachers}</label>
                                {canWrite && (
                                    <select
                                        value=""
                                        onChange={(e) => {
                                            addAssignmentTeacher(e.target.value);
                                            e.target.value = '';
                                        }}
                                        className="h-9 max-w-[12rem] rounded-lg border border-slate-300 px-2 text-xs"
                                    >
                                        <option value="">{t.addTeacher}</option>
                                        {(viewingLesson.availableTeachers ?? [])
                                            .filter((teacher) => !(lessonEditForm.data.assignments ?? [])
                                                .some((assignment) => assignment.teacher_id === teacher.id))
                                            .map((teacher) => (
                                                <option key={teacher.id} value={teacher.id}>
                                                    {teacher.name}
                                                </option>
                                            ))}
                                    </select>
                                )}
                            </div>

                            {(lessonEditForm.data.assignments ?? []).length === 0 ? (
                                <p className="rounded-lg border border-dashed border-slate-200 px-3 py-2 text-sm text-slate-500">
                                    {t.selectTeacher}
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {(lessonEditForm.data.assignments ?? []).map((assignment) => (
                                        <div
                                            key={assignment.teacher_id}
                                            className="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="text-sm font-medium text-slate-800">
                                                    {teacherNameById(viewingLesson.availableTeachers, assignment.teacher_id)}
                                                </div>
                                            </div>
                                            <div className="w-28">
                                                <label className="mb-1 block text-xs font-medium text-slate-600">
                                                    {t.hoursPerWeek}
                                                </label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    max="999"
                                                    value={assignment.hours}
                                                    onChange={(e) => updateAssignmentHours(
                                                        assignment.teacher_id,
                                                        e.target.value,
                                                    )}
                                                    disabled={!canWrite}
                                                    placeholder={t.hoursPlaceholder}
                                                    className="h-9 w-full rounded-lg border border-slate-300 px-2 text-sm disabled:bg-slate-100"
                                                    required
                                                />
                                            </div>
                                            {canWrite && (
                                                <button
                                                    type="button"
                                                    title={t.removeTeacher}
                                                    onClick={() => removeAssignmentTeacher(assignment.teacher_id)}
                                                    className="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            )}
                            {lessonEditForm.errors.assignments && (
                                <p className="mt-1 text-sm text-red-600">{lessonEditForm.errors.assignments}</p>
                            )}
                        </div>

                        <div className="flex justify-end gap-2 pt-1">
                            <button
                                type="button"
                                onClick={() => {
                                    setViewingLesson(null);
                                    lessonEditForm.reset();
                                    lessonEditForm.clearErrors();
                                }}
                                className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            >
                                {t.cancel}
                            </button>
                            {canWrite && (
                                <button
                                    type="submit"
                                    disabled={lessonEditForm.processing || !lessonEditValid}
                                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
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
                    <p className="text-sm text-slate-600">{pendingDeleteMessage}</p>
                    {pendingDelete.label && (
                        <p className="mt-2 text-sm font-semibold text-slate-900">{pendingDelete.label}</p>
                    )}
                    <div className="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setPendingDelete(null)}
                            className="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                        >
                            {t.cancel}
                        </button>
                        <button
                            type="button"
                            onClick={confirmPendingDelete}
                            className="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                        >
                            {t.confirm}
                        </button>
                    </div>
                </Modal>
            )}
        </AdminLayout>
    );
}
