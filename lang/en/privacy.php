<?php

return [
    'page_privacy' => 'Privacy policy',
    'page_deletion' => 'Data deletion',
    'app_name' => 'AUB',
    'updated' => 'Last updated: :date',
    'language' => 'Language',
    'authoritative' => 'The Italian version is the reference text.',
    'address_missing' => 'The controller’s registered address is not entered in this system yet.',
    'email_missing' => 'A dedicated privacy email is not entered in this system yet. Send a deletion request with the form on this page or in the app.',
    'contact_email' => 'Privacy contact: :email',
    'deletion_link' => 'Request account deletion',
    'privacy_link' => 'Privacy policy',
    'form_email' => 'Account email',
    'form_role' => 'Role',
    'role_student' => 'Student',
    'role_parent' => 'Parent or guardian',
    'role_teacher' => 'Teacher',
    'role_other' => 'Other',
    'form_message' => 'Message (optional)',
    'form_confirm' => 'I ask the academy to review deletion of this account.',
    'form_privacy' => 'I have read the privacy policy.',
    'form_submit' => 'Submit the request',
    'form_received' => 'The request has been recorded. The academy reviews it before anything is removed. This form does not delete the account by itself, and the system does not send an automatic confirmation email.',
    'form_intro' => 'You can ask for deletion without reinstalling or opening the app. The request does not delete data immediately. An administrator checks it and only then applies the removal rules for that role.',
    'what_removed' => 'What is removed after an administrator completes the request',
    'what_kept' => 'What may remain',
    'removed_login' => 'Sign-in, password, sessions and API tokens for the account.',
    'removed_chat' => 'Text and files of messages sent by that account. A class conversation itself stays.',
    'removed_parent' => 'For a parent: name, contacts and identity document stored on the parent card. The linked student is not deleted.',
    'removed_teacher' => 'For a teacher: contacts, profile photo, private notes about students, and GPS coordinates on lesson check-ins. Lessons and attendance stay.',
    'removed_student_photo' => 'For a student: profile photo and app access. The student card, attendance, documents and medical certificate stay, because the academy has not yet set how long to keep them.',
    'kept_records' => 'Educational and administrative records for which the academy has not set a retention period.',
    'kept_logs' => 'Logs of administrative actions.',
    'process' => 'After submission the status is pending. An administrator can mark it verified, reject it, or complete it. Only completion turns off access and applies the role rules.',
    'deletion_password_invalid' => 'The password is not correct.',
    'deletion_app_not_available' => 'Self-service deletion is not available for staff accounts.',
    'sections' => [
        [
            'heading' => 'Who controls the data',
            'body' => [
                'The controller named in this system is :name. OwlSolutions is not the controller. OwlSolutions may act as a technical provider when it accesses the server, database or admin panel for the academy.',
            ],
        ],
        [
            'heading' => 'What the service is',
            'body' => [
                'AUB is the academy’s digital system. Students, parents or guardians, teachers and academy staff use it for accounts, classes, schedules, attendance, messages, required documents and medical certificates.',
            ],
        ],
        [
            'heading' => 'Which data',
            'body' => [
                'Depending on the role, the system may hold a name, contacts, tax code, date and place of birth, address, photo, class and courses, schedule, attendance, a teacher’s note about a student, uploaded documents, a medical certificate, messages, notice acknowledgements, access tokens and staff action logs.',
                'Student data may relate to minors. It is used to run the school, not for advertising profiling.',
            ],
        ],
        [
            'heading' => 'Why',
            'body' => [
                'The actual purposes are account management, enrolment and classes, schedules, attendance, messages between the academy and families or teachers, required documents, medical certificates, teachers’ operational notes, account protection and system administration.',
            ],
        ],
        [
            'heading' => 'Legal basis',
            'body' => [
                'One basis does not cover every operation. Depending on the operation, processing may rely on the educational relationship, a legal obligation, a legitimate interest of running the school, or consent where that specific processing requires it. This page does not decide the basis for every field.',
            ],
        ],
        [
            'heading' => 'Minors',
            'body' => [
                'A student account may belong to a minor. A linked parent or guardian can see that child’s data within the parent role. A teacher sees students on that teacher’s courses, not every family’s archive. Minors’ information is not used for advertising profiling.',
            ],
        ],
        [
            'heading' => 'Sharing',
            'body' => [
                'Data sits on the application server and is served over HTTPS. OwlSolutions may access it as a technical provider. If an AI provider is switched on in settings, a medical-certificate file or text sent to that assistant leaves this server for that provider. Mail in this system is not an external email service today, so a deletion request does not send an automatic email. Apple and Google matter only if the app is installed from their stores, or if the phone sends a location for a lesson check-in, which is stored on the academy server.',
            ],
        ],
        [
            'heading' => 'Transfers',
            'body' => [
                'The project does not record the country of the hosting contract. If an external AI provider is enabled, content sent to it may be processed outside this server and, depending on that provider, outside the European Economic Area. The software does not fix a country list.',
            ],
        ],
        [
            'heading' => 'Security',
            'body' => [
                'The site uses HTTPS. Passwords are stored as hashes. The app uses access tokens. Personal files are outside the public site and are served only after a role check. Staff see the sections their role allows. Administrative actions are logged. None of this makes the system impossible to breach.',
            ],
        ],
        [
            'heading' => 'Retention',
            'body' => [
                'The academy has not set a retention period for each category in the software. Deleting an account does not erase every row at once. Student cards, enrolment, attendance, student documents, medical certificates, lessons and administrative logs stay until a retention decision exists. Sign-in data is removed when an administrator completes the request. For a parent or teacher, that person’s contact details are replaced. Immediate deletion of every academic record is not what the system does.',
            ],
        ],
        [
            'heading' => 'Rights',
            'body' => [
                'You can ask for access, correction, erasure, restriction, and objection where it applies. Portability applies only where the processing allows it. Where processing is based on consent, that consent can be withdrawn for the future. You can complain to the Garante per la protezione dei dati personali. This page does not invent a web address for that authority.',
            ],
        ],
        [
            'heading' => 'Account deletion',
            'body' => [
                'Send a request from :deletion or in the app under Profile, Privacy and data, Delete account. The public form does not delete anything by itself. In the app, after the password confirmation, the current session is closed and the request waits until an administrator completes it.',
            ],
        ],
        [
            'heading' => 'Changes',
            'body' => [
                'This text changes when the system’s behaviour changes. The date at the top is the last update of this page.',
            ],
        ],
    ],
];
