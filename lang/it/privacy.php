<?php

return [
    'page_privacy' => 'Informativa sulla privacy',
    'page_deletion' => 'Eliminazione dei dati',
    'app_name' => 'AUB',
    'updated' => 'Ultimo aggiornamento: :date',
    'language' => 'Lingua',
    'authoritative' => 'La versione italiana è il testo di riferimento.',
    'address_missing' => 'L’indirizzo registrato del titolare non è ancora inserito in questo sistema.',
    'email_missing' => 'Un indirizzo email dedicato alla privacy non è ancora inserito in questo sistema. La richiesta di eliminazione si invia dal modulo in questa pagina o dall’app.',
    'contact_email' => 'Contatto privacy: :email',
    'deletion_link' => 'Richiedi l’eliminazione dell’account',
    'privacy_link' => 'Informativa sulla privacy',
    'form_email' => 'Email dell’account',
    'form_role' => 'Ruolo',
    'role_student' => 'Studente',
    'role_parent' => 'Genitore o tutore',
    'role_teacher' => 'Insegnante',
    'role_other' => 'Altro',
    'form_message' => 'Messaggio (facoltativo)',
    'form_confirm' => 'Chiedo la revisione dell’eliminazione di questo account.',
    'form_privacy' => 'Ho letto l’informativa sulla privacy.',
    'form_submit' => 'Invia la richiesta',
    'form_received' => 'La richiesta è stata registrata. L’accademia la esamina prima di rimuovere qualcosa. Questo modulo non cancella l’account da solo e il sistema non invia un’email automatica di conferma.',
    'form_intro' => 'Si può chiedere l’eliminazione senza reinstallare o aprire l’app. La richiesta non cancella subito i dati: un amministratore la verifica e solo dopo esegue la rimozione prevista per quel ruolo.',
    'what_removed' => 'Che cosa viene rimosso, dopo la conferma dell’amministratore',
    'what_kept' => 'Che cosa può restare',
    'removed_login' => 'Accesso, password, sessioni e token dell’account.',
    'removed_chat' => 'Il testo e i file dei messaggi inviati da quell’account. La conversazione di classe resta.',
    'removed_parent' => 'Per un genitore: nome, contatti e documento d’identità salvati sulla scheda del genitore. Lo studente collegato non viene cancellato.',
    'removed_teacher' => 'Per un insegnante: contatti, foto profilo, note private sugli studenti e coordinate GPS dei check-in. Lezioni e presenze restano.',
    'removed_student_photo' => 'Per uno studente: foto profilo e accesso all’app. La scheda studente, le presenze, i documenti e il certificato medico restano, perché l’accademia non ha ancora definito per quanto tempo conservarli.',
    'kept_records' => 'Registri didattici e amministrativi per i quali non esiste ancora un termine di conservazione deciso dall’accademia.',
    'kept_logs' => 'Log delle azioni amministrative.',
    'process' => 'Dopo l’invio lo stato è «in attesa». L’amministratore può verificarla, rifiutarla o completarla. Solo il completamento disattiva l’accesso e applica le regole del ruolo.',
    'deletion_password_invalid' => 'La password non è corretta.',
    'deletion_app_not_available' => 'L’eliminazione self-service non è disponibile per gli account del personale.',
    'sections' => [
        [
            'heading' => 'Chi tratta i dati',
            'body' => [
                'Il titolare del trattamento indicato in questo sistema è :name. OwlSolutions non è il titolare: può operare come fornitore tecnico quando accede al server, al database o al pannello per conto dell’accademia.',
            ],
        ],
        [
            'heading' => 'Che cos’è il servizio',
            'body' => [
                'AUB è il sistema digitale dell’accademia. Lo usano studenti, genitori o tutori, insegnanti e personale dell’accademia per account, corsi, orari, presenze, comunicazioni, documenti richiesti e certificati medici.',
            ],
        ],
        [
            'heading' => 'Quali dati',
            'body' => [
                'A seconda del ruolo, il sistema può contenere nome, contatti, codice fiscale, data e luogo di nascita, indirizzo, foto, classe e corsi, orario, presenze, note dell’insegnante sullo studente, documenti caricati, certificato medico, messaggi, conferme di lettura degli avvisi, token di accesso e log delle azioni del personale.',
                'I dati degli studenti possono riguardare minorenni. Sono usati per l’organizzazione della scuola, non per profilazione pubblicitaria.',
            ],
        ],
        [
            'heading' => 'Perché',
            'body' => [
                'Gli scopi effettivi sono la gestione degli account, iscrizioni e classi, orari, presenze, messaggi tra accademia e famiglie o insegnanti, documenti richiesti, certificati medici, note operative degli insegnanti, protezione degli account e amministrazione del sistema.',
            ],
        ],
        [
            'heading' => 'Base del trattamento',
            'body' => [
                'Non c’è una sola base per tutto. A seconda dell’operazione il trattamento può poggiare sull’esecuzione del rapporto con l’accademia, su un obbligo di legge, su un interesse legittimo dell’organizzazione scolastica, oppure sul consenso quando quello specifico trattamento lo richiede. Questa informativa non stabilisce da sola quale base valga per ogni singolo dato.',
            ],
        ],
        [
            'heading' => 'Minori',
            'body' => [
                'Un account studente può riferirsi a un minore. Il genitore o tutore collegato vede i dati di quel figlio, nei limiti del proprio ruolo. Un insegnante vede gli studenti dei propri corsi, non l’archivio di tutte le famiglie. I dati dei minori non sono usati per pubblicità profilata.',
            ],
        ],
        [
            'heading' => 'Con chi sono condivisi',
            'body' => [
                'I dati stanno sul server dell’applicazione, accessibile in HTTPS. OwlSolutions può accedervi come fornitore tecnico. Se nelle impostazioni è attivo un fornitore di intelligenza artificiale, un file di certificato medico o un testo inviato a quell’assistente esce dal server verso quel fornitore. La posta del sistema, oggi, non è un servizio email esterno: le richieste di eliminazione non producono un’email automatica. Apple e Google entrano in gioco solo se l’app è installata dai loro store o se il telefono invia la posizione per il check-in della lezione, che resta sul server dell’accademia.',
            ],
        ],
        [
            'heading' => 'Trasferimenti',
            'body' => [
                'Il progetto non registra il paese del contratto di hosting. Se un fornitore di intelligenza artificiale esterno è acceso, i contenuti inviati a quel fornitore possono essere trattati fuori da questo server e, a seconda del fornitore, fuori dallo Spazio economico europeo. L’elenco dei paesi non è fissato nel software.',
            ],
        ],
        [
            'heading' => 'Sicurezza',
            'body' => [
                'Il sito usa HTTPS. Le password sono memorizzate in forma hash. L’accesso all’app usa token. I file delle persone stanno in un’area non pubblica e sono serviti solo dopo un controllo del ruolo. Il personale vede le sezioni consentite dal proprio ruolo. Esistono log delle azioni amministrative. Nessuna di queste misure rende il sistema invulnerabile.',
            ],
        ],
        [
            'heading' => 'Conservazione',
            'body' => [
                'L’accademia non ha definito nel software tempi di conservazione diversi per ogni categoria. L’eliminazione dell’account non fa sparire subito ogni riga. Restano i registri per i quali non è stata decisa una cancellazione: scheda studente, iscrizioni, presenze, documenti e certificato medico dello studente, lezioni, e i log amministrativi. I dati di accesso e, per genitore e insegnante, i dati di contatto della persona vengono invece sostituiti o rimossi quando un amministratore completa la richiesta. Non è promessa una cancellazione immediata di tutto.',
            ],
        ],
        [
            'heading' => 'Diritti',
            'body' => [
                'Si possono chiedere accesso, rettifica, cancellazione, limitazione, e opposizione quando applicabile. La portabilità si applica solo dove il trattamento lo prevede. Il consenso, se è la base di un trattamento, si può revocare per il futuro. Si può proporre reclamo al Garante per la protezione dei dati personali. Questa pagina non inventa un indirizzo del Garante oltre al nome dell’autorità.',
            ],
        ],
        [
            'heading' => 'Eliminazione dell’account',
            'body' => [
                'La richiesta si invia da :deletion oppure dall’app, in Profilo, Privacy e dati, Elimina account. Il modulo pubblico non cancella nulla da solo. Nell’app, dopo la conferma con la password, la sessione corrente viene chiusa e la richiesta resta in attesa finché un amministratore non la completa.',
            ],
        ],
        [
            'heading' => 'Modifiche',
            'body' => [
                'Il testo cambia quando cambia il comportamento del sistema. La data in cima è l’ultimo aggiornamento di questa pagina.',
            ],
        ],
    ],
];
