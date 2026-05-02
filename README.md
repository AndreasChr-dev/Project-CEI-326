# README – Project CEI-326 

## Περιγραφή Εφαρμογής

Η εφαρμογή **Παρακολούθησης Πόθεν Έσχες** είναι μια πλήρης διαδικτυακή πλατφόρμα για την καταχώριση, διαχείριση και δημόσια προβολή των δηλώσεων περιουσιακής κατάστασης των αξιωματούχων της Κυπριακής Δημοκρατίας.

Σε αυτό το application υλοποιήθηκαν τα εξής module:
- **adminModule** — Διαχείριση χρηστών, αξιωματούχων, υποβολών, αναφορών και ρυθμίσεων από διαχειριστές
- **searchModule** — Δημόσια αναζήτηση, λίστα αξιωματούχων, προβολή δηλώσεων και στατιστικά
- **submitModule** — Υποβολή και διαχείριση δηλώσεων από τους ίδιους τους αξιωματούχους
- **API** — Δημόσιο REST API (read-only) για πρόσβαση σε δεδομένα αξιωματούχων, δηλώσεων και στατιστικών μέσω JSON

---

## Μέλη Ομάδας

### Ανδρέας Χριστοδούλου — AM: 27937

| Αρχείο | Περιγραφή |
|--------|-----------|
| `adminModule/users.php` | Διαχείριση χρηστών: προσθήκη official/admin, επεξεργασία στοιχείων, διαγραφή, λίστα με roles |
| `adminModule/manage_submissions.php` | Εποπτεία υποβολών: φίλτρα ανά έτος/κατάσταση, προβολή assets, αλλαγή status δήλωσης |
| `adminModule/reports.php` | Αναφορές συστήματος: εξαγωγή δεδομένων, φίλτρα, εκτυπώσιμες αναφορές ανά αξιωματούχο/έτος |
| `adminModule/settings.php` | Ρυθμίσεις εφαρμογής: διαχείριση κομμάτων και θέσεων (CRUD) |
| `index.php` | Αρχική σελίδα: αν ο χρήστης είναι συνδεδεμένος τον ανακατευθύνει στο dashboard, αλλιώς στο login |
| `assets/css/style.css` | Κεντρικό stylesheet: CSS variables, θεματισμός, responsive layout για όλες τις σελίδες |
| `database/schema.sql` | Σχεδιασμός βάσης δεδομένων: 6 πίνακες, foreign keys, constraints, ENUM fields |
| `README.md` | Πλήρης τεκμηρίωση έργου: περιγραφή, οδηγίες εγκατάστασης, κατανομή εργασίας |

---

### Αθανάσιος Παπασπύρου — AM: 30597

| Αρχείο | Περιγραφή |
|--------|-----------|
| `searchModule/dashboard.php` | Κεντρικό dashboard: στατιστικά cards, γρήγορη πλοήγηση, σύνοψη δεδομένων συστήματος |
| `searchModule/list.php` | Δημόσια λίστα αξιωματούχων: αναζήτηση με keyword, φίλτρα κόμματος/επαρχίας/θέσης |
| `searchModule/statistics.php` | Στατιστικά: πίνακες και γραφήματα ανά έτος, κόμμα, κατηγορία assets και επαρχία |
| `auth/login.php` | Σύνδεση χρηστών: validation, session management, role-based redirect |
| `database/seed.sql` | Δεδομένα δοκιμής: κόμματα, θέσεις, χρήστες, officials, δηλώσεις, assets |
| `API/index.php` | REST API (read-only, GET): endpoints για officials, declarations, declaration (με assets), statistics — επιστρέφει JSON με φίλτρα ανά έτος/κόμμα/official |

---

### Ευάγγελος Πελεκάνου — AM: 27862

| Αρχείο | Περιγραφή |
|--------|-----------|
| `submitModule/submit.php` | Υποβολή δήλωσης: δημιουργία/επεξεργασία δήλωσης, προσθήκη/διαγραφή assets ανά κατηγορία |
| `submitModule/my_submissions.php` | Οι δηλώσεις μου: ιστορικό δηλώσεων, προβολή λεπτομερειών, οριστική υποβολή (draft → submitted) |
| `submitModule/profile.php` | Προφίλ αξιωματούχου: προβολή στοιχείων, ενημέρωση email και κωδικού πρόσβασης |
| `includes/header.php` | Κοινή κεφαλίδα: navbar με role-based πλοήγηση, dark mode toggle, cache headers |
| `auth/register.php` | Εγγραφή νέου χρήστη: validation, έλεγχος μοναδικότητας, password hashing |
| `auth/logout.php` | Αποσύνδεση: καταστροφή session, redirect στη σελίδα login |

---

## Σκοπός

Ο σκοπός του application είναι η υλοποίηση πλήρους εφαρμογής που περιλαμβάνει:

- Σχεδιασμό και δημιουργία εκτεταμένης βάσης δεδομένων (schema.sql)
- Ασφαλή σύνδεση με MySQL μέσω PDO και Prepared Statements
- Πλήρες σύστημα εγγραφής, σύνδεσης και αποσύνδεσης χρηστών με Session Guard
- Role-based Access Control για admin, official και user
- adminModule για πλήρη διαχείριση του συστήματος
- searchModule για δημόσια αναζήτηση και στατιστικά δεδομένων
- submitModule για υποβολή δηλώσεων από αξιωματούχους
- Σύστημα draft/submitted για τη ροή υποβολής δηλώσεων Πόθεν Έσχες
- Δημόσιο REST API (read-only) για πρόσβαση τρίτων σε δεδομένα μέσω JSON

---

## Περιορισμοί Ασφάλειας

Σύμφωνα με την εκφώνηση, τηρήθηκαν αυστηρά οι παρακάτω κανόνες:

- **Prepared Statements πάντα** — απαγορεύεται η συνένωση string σε SQL queries
- **password_hash()** — οι κωδικοί αποθηκεύονται μόνο κρυπτογραφημένοι (bcrypt), ποτέ plain-text
- **htmlspecialchars()** — σε κάθε echo δεδομένων χρήστη για προστασία από XSS
- **exit μετά από κάθε header()** — για αποφυγή συνέχισης εκτέλεσης κώδικα μετά από redirect
- **Χωρίς die($e->getMessage())** — τα credentials της βάσης δεν εκτίθενται σε σφάλματα
- **Session Guard** — κάθε προστατευμένη σελίδα ελέγχει `$_SESSION['user_id']` και `role` πριν εκτελεστεί
- **Role-based Access Control** — admin, official και user βλέπουν μόνο ό,τι τους αντιστοιχεί

---

## Βάση Δεδομένων

Δημιουργός `schema.sql`: **Ανδρέας Χριστοδούλου** — Δεδομένα δοκιμής `seed.sql`: **Αθανάσιος Παπασπύρου**

Η βάση `cei326_lab` περιέχει 6 πίνακες:

| Πίνακας | Περιγραφή |
|---------|-----------|
| `parties` | Κόμματα (id, name, short_name, created_at) |
| `positions` | Θέσεις αξιωματούχων (id, title, created_at) |
| `users` | Λογαριασμοί σύνδεσης (id, username, email, password_hash, role, created_at) |
| `officials` | Αξιωματούχοι (id, user_id FK, first_name, last_name, phone, district, party_id FK, position_id FK, created_at) |
| `declarations` | Δηλώσεις Πόθεν Έσχες (id, official_id FK, declaration_year, status ENUM('draft','submitted'), submitted_at, created_at) |
| `declaration_assets` | Περιουσιακά στοιχεία (id, declaration_id FK, category, description, amount, currency, notes, created_at) |

---

## Οδηγίες Εγκατάστασης

1. Εγκατάσταση XAMPP και εκκίνηση Apache + MySQL
2. Δημιουργία βάσης δεδομένων(sql):

CREATE DATABASE cei326_lab;

3. Import αρχείων(bash):

mysql -u root -p cei326_lab < database/schema.sql
mysql -u root -p cei326_lab < database/seed.sql