OC.L10N.register(
    "user_ldap",
    {
    "The Base DN appears to be wrong" : "בסיס DN נראה כשגוי",
    "Testing configuration…" : "בדיקת תצורה...",
    "Configuration incorrect" : "הגדרה שגויה",
    "Configuration incomplete" : "הגדרה לא מלאה",
    "Configuration OK" : "הגדרה בסדר",
    "Select groups" : "בחירת קבוצות",
    "Select object classes" : "בחירת מחלקות עצמים",
    "Please check the credentials, they seem to be wrong." : "יש לבדוק את פרטי הכניסה, נראה שהם שגויים",
    "Please specify the port, it could not be auto-detected." : "יש לספק את שער הכניסה - פורט, לא ניתן היה לאתרו בצורה אוטומטית",
    "Base DN could not be auto-detected, please revise credentials, host and port." : "לא ניתן היה לאתר באופן אוטומטי את בסיס DN, יש להחליף את פרטי הכניסה, פרטי שרת ושער גישה - פורט.",
    "Could not detect Base DN, please enter it manually." : "לא ניתן היה לאתר את בסיס DN, יש להכניסו באופן ידני.",
    "{nthServer}. Server" : "{nthServer}. שרת",
    "No object found in the given Base DN. Please revise." : "לא אותר אוביקט בבסיס DN שסופק. יש להחליף.",
    "More than 1,000 directory entries available." : "קיימים יותר מ- 1,000 רשומות ספריה.",
    " entries available within the provided Base DN" : " קיימות רשומות מתוך בסיס ה- DN שסופק",
    "An error occurred. Please check the Base DN, as well as connection settings and credentials." : "אירעה שגיאה. יש לבדוק את בסיס ה- DN, כמו גם את הגדרות החיבור ופרטי הכניסה.",
    "Do you really want to delete the current Server Configuration?" : "האם אכן למחוק את הגדרות השרת הנוכחיות?האם באמת ברצונך למחוק את הגדרות השרת הנוכחיות?",
    "Confirm Deletion" : "אישור המחיקה",
    "Mappings cleared successfully!" : "מיפויים נוקו בהצלחה!",
    "Error while clearing the mappings." : "שגיאה בזמן ניקוי המיפויים.",
    "Anonymous bind is not allowed. Please provide a User DN and Password." : "קישור אננונימי אינו מותר. יש לספק שם משתמש DN וסיסמא.",
    "LDAP Operations error. Anonymous bind might not be allowed." : "שגיאת פעילויות LDAP. יתכן שקישור אנונימי אינו מותר.",
    "Saving failed. Please make sure the database is in Operation. Reload before continuing." : "שמירה נכשלה. יש לבדוק אם מסד הנתונים פעיל. יש לטעון מחדש לפני המשך.",
    "Switching the mode will enable automatic LDAP queries. Depending on your LDAP size they may take a while. Do you still want to switch the mode?" : "שינוי המצב יאפשר שאילתות LDAP אוטמטיות. בהתאם לגודל ה- LDAP שלך ייתכן והפעולה תיקח זמן רב. האם ברצונך לשנות את המצב?",
    "Mode switch" : "שינוי מצב",
    "Select attributes" : "בחירת מאפיינים",
    "User not found. Please check your login attributes and username. Effective filter (to copy-and-paste for command line validation): <br/>" : "משתמש לא אותר. יש לבדוק את מאפייני ההתחברות ושם המשתמש. מסנן אפקטיבי (העתקה והדבקה לאימות שורת פקודה):<br/>",
    "User found and settings verified." : "משתמש אותר והגדרות אומתו.",
    "Settings verified, but more than one user was found. Only the first will be able to login. Consider a more narrow filter." : "הגדרות אומתו, אך נמצא יותר ממשתמש אחד. יש לקחת בחשבון שרק המשתמש הראשון יוכל להתחבר. יש לשקול סינון צר יותר.",
    "An unspecified error occurred. Please check the settings and the log." : "אירעה שגיאה לא מזוהה. יש לבדוק את ההגדרות ואת הלוג.",
    "The search filter is invalid, probably due to syntax issues like uneven number of opened and closed brackets. Please revise." : "סינון החיפוש אינו חוקי. ככל הנראה בשל שיאה תחבירית כגון מספר לא שווה של פתח-סוגריים וסגור-סוגריים. יש לתקן.",
    "A connection error to LDAP / AD occurred, please check host, port and credentials." : "אירעה שגיאת חיבור ל- LDAP / AD, יש לבדוק את השרת, שער החיבור - פורט ופרטי הכניסה. ",
    "The %uid placeholder is missing. It will be replaced with the login name when querying LDAP / AD." : "שומר המקום %uid חסר. הוא יוחלף עם שם המשתמש בזמן שאילתת LDAP / AD.",
    "Please provide a login name to test against" : "יש לספק שם משתמש לבדיקה מולו",
    "The group box was disabled, because the LDAP / AD server does not support memberOf." : "שדה הקבוצה נוטרל, כיוון ששרת ה- LDAP / AD לא תומך ב- memberOf.",
    "Server" : "שרת",
    "Users" : "משתמשים",
    "Login Attributes" : "פרטי כניסה",
    "Groups" : "קבוצות",
    "The configuration is invalid: anonymous bind is not allowed." : "התצורה אינה חוקית: חיבור אנונימי אסור",
    "The configuration is valid and the connection could be established!" : "התצורה תקפה וניתן לבצע חיבור!",
    "The configuration is valid, but the Bind failed. Please check the server settings and credentials." : "התצורה תקפה, אך הקישור נכשל. יש לבדוק את הגדרות השרת והחיבור.",
    "The configuration is invalid. Please have a look at the logs for further details." : "התצורה אינה חוקית. יש לבדוק את הלוגים לפרטים נוספים.",
    "Failed to delete the server configuration" : "כשל במחיקת הגדרות השרת",
    "Failed to clear the mappings." : "כשל בניקוי המיפויים.",
    "No data specified" : "לא הוגדר מידע",
    " Could not set configuration %s" : " לא ניתן היה לקבוע הגדרות %s",
    "Action does not exist" : "פעולה לא קיימת",
    "_%s group found_::_%s groups found_" : ["אותרה %s קבוצה","אותרו %s קבוצות","אותרו %s קבוצות","אותרו %s קבוצות"],
    "_%s user found_::_%s users found_" : ["אותר %s משתמש","אותרו %s משתמשים","אותרו %s משתמשים","אותרו %s משתמשים"],
    "Could not detect user display name attribute. Please specify it yourself in advanced ldap settings." : "לא אותר מאפיין שם תצוגה למשתמש. יש לספק אותו בעצמך בהגדרות ldap מתקדמות.",
    "Could not find the desired feature" : "לא אותרה התכונה הרצויה",
    "Test Configuration" : "בדיקת הגדרות",
    "Groups meeting these criteria are available in %s:" : "קבוצות העומדות בקריטריון זה זמינות ב- %s:",
    "Only these object classes:" : "מחלקות עצמים אלו בלבד:",
    "Only from these groups:" : "רק מקבוצות אלו:",
    "Search groups" : "חיפוש בקבוצות",
    "Available groups" : "קבוצות זמינות",
    "Selected groups" : "קבוצות נבחרות",
    "Edit LDAP Query" : "עריכת שאילתת LDAP",
    "LDAP Filter:" : "מסנן LDAP:",
    "The filter specifies which LDAP groups shall have access to the %s instance." : "המסנן הקובע לאיזו קבוצת LDAP תהיה יכולת כניסה למקרה %s.",
    "Verify settings and count groups" : "מאמת הגדרות וסופר קבוצות",
    "When logging in, %s will find the user based on the following attributes:" : "כאשר מתחברים, %s יחפש את המשתמש על פי המאפיינים הבאים:",
    "LDAP / AD Username:" : "שם משתמש LDAP / AD:",
    "Allows login against the LDAP / AD username, which is either uid or samaccountname and will be detected." : "מאפשר התחברות אל מול שם משתמש LDAP / AD, שהוא רק uid או samaccountname ויזוהה.",
    "LDAP / AD Email Address:" : "כתובת דואר אלקטרוני LDAP / AD:",
    "Allows login against an email attribute. Mail and mailPrimaryAddress will be allowed. WARNING: Disabling login with email might require enabling strict login checking to be effective, please refer to ownCloud documentation for more details!" : "מאפשר התחברות כנגד מאפיין דואר אלקטרוני. דואר וכתובת דואר אלקטרוני ראשית יאושרו. אזהרה: השבתת ההתחברות באמצעות דואר אלקטרוני עשויה לדרוש הפעלת בדיקת כניסה קפדנית כדי להיות יעילה, יש לעיין בתיעוד ownCloud לקבלת פרטים נוספים!",
    "Other Attributes:" : "מאפיינים נוספים:",
    "Defines the filter to apply, when login is attempted. %%uid replaces the username in the login action. Example: \"uid=%%uid\"" : "מגדיר את הסינון הפעיל, כשיש ניסיון התחברות. %%uid מחליף את שם המשתמש בפעולת ההתחברות. לדוגמא: \"uid=%%uid\"",
    "Test Loginname" : "בדיקת שם התחברות",
    "Verify settings" : "מאמת הגדרות",
    "1. Server" : "1. שרת",
    "%s. Server:" : "%s. שרת:",
    "Add a new and blank configuration" : "הוספת תצורה חדשה וריקה",
    "Copy current configuration into new directory binding" : "מעתיק תצורה נוכחית אל תוך תיקייה חדשה",
    "Delete the current configuration" : "מחיקת תצורה נוכחית",
    "Host" : "מארח",
    "Port" : "פורט",
    "You can omit the protocol, except you require SSL. Then start with ldaps://" : "ניתן להשמיט את הפרוטוקול, אך SSL מחייב. לפיכך יש להתחיל עם ldaps://",
    "Use StartTLS support" : "משתמש בתמיכת StartTLS",
    "Enable StartTLS support (also known as LDAP over TLS) for the connection.  Note that this is different than LDAPS (LDAP over SSL) which doesn't need this checkbox checked. You'll need to import the LDAP server's certificate in your %s server." : "מאפשר תמיכת StartTLS (מוכר גם כ- LDAP מעל TLS) עבור התחברות.  תשומת לב לכך שלא מדובר ב- LDAPS (LDAP מעל SSL) שאינו מצריך סימון תיבה זו. יהיה צורך ליבא את תעודת ה- LDAP של השרת לשרת ה- %s שלך.",
    "User DN" : "DN משתמש",
    "The DN of the client user with which the bind shall be done, e.g. uid=agent,dc=example,dc=com. For anonymous access, leave DN and Password empty." : "ה- DN של משתמש הלקוח שבו החיבור יעשה, למשל uid=agent,dc=example,dc=com. לחיבור אנונימי, יש להשאיר את ה- DN והסיסמא ריקים.",
    "Password" : "סיסמא",
    "For anonymous access, leave DN and Password empty." : "לגישה אנונימית, השאר את הDM והסיסמא ריקים.",
    "One Base DN per line" : "DN בסיסי אחד לשורה",
    "You can specify Base DN for users and groups in the Advanced tab" : "ניתן לציין DN בסיסי למשתמשים ולקבוצות בלשונית מתקדם",
    "Detect Base DN" : "גילוי DN בסיסי",
    "Test Base DN" : "בדיקת DN בסיסי",
    "Manually enter LDAP filters (recommended for large directories)" : "הכנסת מסנני LDAP ידנית (מומלץ עבוק תיקיות גדולות)",
    "Avoids automatic LDAP requests. Better for bigger setups, but requires some LDAP knowledge." : "נמנע מבקשות אוטומטיות של LDAP. מועדף עבור התקנות גדולות, אבל מחייב ידע מסויים של LDAP.",
    "%s access is limited to users meeting these criteria:" : "%s גישה מוגבלת למשתמשים שעונים על קריטריונים אלו:",
    "The most common object classes for users are organizationalPerson, person, user, and inetOrgPerson. If you are not sure which object class to select, please consult your directory admin." : "העצמים הבסיסיים למשתמשים הם organizationalPerson, person, user, וכן inetOrgPerson. אם אינך בטוח איזה עצם לבחור, יש להתייעף עם מנהל התיקייה.",
    "The filter specifies which LDAP users shall have access to the %s instance." : "הסינון קובע לאיזו משתמשי LDAP תהיה יכולת כניסה למקרה %s.",
    "Verify settings and count users" : "מאמת הגדרות וסופר משתמשים",
    "Saving" : "שמירה",
    "Back" : "אחורה",
    "Continue" : "המשך",
    "LDAP" : "LDAP",
    "Advanced" : "מתקדם",
    "Expert" : "מומחה",
    "Help" : "עזרה",
    "<b>Warning:</b> Apps user_ldap and user_webdavauth are incompatible. You may experience unexpected behavior. Please ask your system administrator to disable one of them." : "<b>אזהרה:</b> יישומים user_ldap ו- user_webdavauth אינם תואמים. תופעות לא מוסברות עלולות להתקיים. כדאי לפנות למנהל המערכת כדי שינטרל אחד מהם.",
    "<b>Warning:</b> The PHP LDAP module is not installed, the backend will not work. Please ask your system administrator to install it." : "<b>אזהרה:</b> מודול PHP LDAP אינו מותקן, צד אחורי לא יעבוד. יש לבקש מהמנהל המערכת להתקין אותו.",
    "Connection Settings" : "הגדרות התחברות",
    "When unchecked, this configuration will be skipped." : "כאשר לא מסומן, נדלג על תצורה זו.",
    "Configuration Active" : "תצורה פעילה",
    "Backup (Replica) Host" : "גיבוי (העתק) שרת",
    "Give an optional backup host. It must be a replica of the main LDAP/AD server." : "יוצר מארח גיבוי אופציונלי. זה חייב להיות העתק של שרת LDAP/AD עיקרי.",
    "Backup (Replica) Port" : "גיבוי (העתק) שער - פורט",
    "Disable Main Server" : "ניטרול שרת עיקרי",
    "Only connect to the replica server." : "חיבור רק להעתק שרת.",
    "Turn off SSL certificate validation." : "כיבוי אימות אישורי אבטחה SSL.",
    "Not recommended, use it for testing only! If connection only works with this option, import the LDAP server's SSL certificate in your %s server." : "אינו מומלץ, לשימוש לניסיון בלבד! אם החיבור עובד רק עם אפשרות זו, יבוא של תעודת SSL של שרת LDAP בשרת %s שלך.",
    "Cache Time-To-Live" : "מטמון זמן חיים - TTL",
    "in seconds. A change empties the cache." : "בשניות. שינוי מרוקן את המטמון.",
    "Network Timeout" : "פסק זמן רשת",
    "timeout for all the ldap network operations, in seconds." : "פסק זמן לכל פעילויות רשת ה- Idap, בשניות.",
    "Directory Settings" : "הגדרות תיקייה",
    "User Display Name Field" : "שדה שם תצוגה למשתמש",
    "The LDAP attribute to use to generate the user's display name." : "תכונת LDAP לשימוש כדי להפיק את שם התצוגה של המשתמש.",
    "2nd User Display Name Field" : "שדה שני לשם תצוגת משתמש",
    "Optional. An LDAP attribute to be added to the display name in brackets. Results in e.g. »John Doe (john.doe@example.org)«." : "אופציונאלי. מאפיין LDAP שיתווסף לפני השם בסוגריים. לדוגמא »John Doe (john.doe@example.org)«.",
    "Base User Tree" : "עץ משתמש בסיסי",
    "One User Base DN per line" : "משתמש DN בסיסי אחד לשורה",
    "User Search Attributes" : "מאפייני חיפוש משתמש",
    "Optional; one attribute per line" : "אופציונאלי; מאפיין אחד בשורה",
    "Each attribute value is truncated to 191 characters" : "כל ערך משתנה מחולק ל- 191 תווים",
    "Group Display Name Field" : "שדה שם תצוגה לקבוצה",
    "The LDAP attribute to use to generate the groups's display name." : "מאפיין LDAP לשימוש בהפקת שם תצוגת הקבוצה.",
    "Base Group Tree" : "עץ קבוצה בסיסי",
    "One Group Base DN per line" : "קבוצת DN בסיסית לשורה",
    "Group Search Attributes" : "מאפייני חיפוש קבוצה",
    "Group-Member association" : "שיוך חברי-קבוצה",
    "Dynamic Group Member URL" : "נתיב חבר קבוצה דינמית",
    "The LDAP attribute that on group objects contains an LDAP search URL that determines what objects belong to the group. (An empty setting disables dynamic group membership functionality.)" : "מאפיין LDAP שבעצם קבוצה מכיל נתיב חיפוש שקובע אילו עצמים שייכים לקבוצה. (הגדרה ריקה מבטלת אפשרות לחברות בקבוצה דינמית.)",
    "Nested Groups" : "קבוצות משנה",
    "When switched on, groups that contain groups are supported. (Only works if the group member attribute contains DNs.)" : "כאשר מופעל, קיימת תמיכה לקבוצות המכילות קבוצות משנה. (עובד רק אם מאפיין חבר הקבוצה מכיל DN-ים.)",
    "Paging chunksize" : "Paging chunksize",
    "Chunksize used for paged LDAP searches that may return bulky results like user or group enumeration. (Setting it 0 disables paged LDAP searches in those situations.)" : "Chunksize משמש לחיפושי paged LDAP שעלולים להחזיר תוצאות גסות כמו ספירת משתמש או קבוצה. (הגדרה כ- 0 מנטרל חיפושי paged LDAP במצבים אלה.)",
    "Special Attributes" : "מאפיינים מיוחדים",
    "Quota Field" : "שדה מכסה",
    "Leave empty for user's default quota. Otherwise, specify an LDAP/AD attribute." : "יש להשאיר ריק לברירת מחדל של מכסת משתמש. לחילופין, יש להגדיר מאפיין LDAP/AD.",
    "Quota Default" : "ברירת מחדל מכסה",
    "Override default quota for LDAP users who do not have a quota set in the Quota Field." : "דריסת ברירת מחדל למכסת משתמשי LDAP שאין להם מכסה מוגדרת בשדה מכסה.",
    "Email Field" : "שדה דואר אלקטרוני",
    "Set the user's email from their LDAP attribute. Leave it empty for default behaviour." : "מגדיר את כתובת הדואר האלקטרוני מתוך מאפיין LDAP. יש להשאיר ריק להתנהגות ברירת מחדל.",
    "User Home Folder Naming Rule" : "כלל קביעת שם תיקיית בית למשתמש",
    "Leave empty for user name (default). Otherwise, specify an LDAP/AD attribute." : "יש להשאיר ריק לשם משתמש (ברירת מחדל). לחילופין, יש להגדיר מאפיין LDAP/AD.",
    "Internal Username" : "שם משתמש פנימי",
    "Internal Username Attribute:" : "מאפיין שם משתמש פנימי:",
    "Override UUID detection" : "דריסת זיהוי UUID",
    "By default, the UUID attribute is automatically detected. The UUID attribute is used to doubtlessly identify LDAP users and groups. Also, the internal username will be created based on the UUID, if not specified otherwise above. You can override the setting and pass an attribute of your choice. You must make sure that the attribute of your choice can be fetched for both users and groups and it is unique. Leave it empty for default behavior. Changes will have effect only on newly mapped (added) LDAP users and groups." : "כברירת מחדל, משתנה ה- UUID מזוהה באופן אוטומטי. משתנה ה- UUID משמש לזהות בוודאות את משתמשי וקבוצות ה- LDAP. בנוסף, שם המשתמש הפנימי יווצר על בסיס ה- UUID, אם לא הוגדר אחרת למעלה. ניתן לדרוס הגדרה זו ולהעביר משתנה לפי בחירתך. יש לוודא שהמשתנה לפי בחירתך ניתן לאחזור למשתמשים ולקבוצות ושהוא יחודי. יש להשאיר ריק להתנהגות ברירת מחדל. שינויים יושפעו למיפויים חדשים (שהוספו) של קבוצות או משתמשי LDAP.",
    "UUID Attribute for Users:" : "מאפייני UUID למשתמשים:",
    "UUID Attribute for Groups:" : "מאפייני UUID לקבוצות:",
    "Username-LDAP User Mapping" : "מיפוי שם משתמש LDAP:",
    "Usernames are used to store and assign (meta) data. In order to precisely identify and recognize users, each LDAP user will have an internal username. This requires a mapping from username to LDAP user. The created username is mapped to the UUID of the LDAP user. Additionally the DN is cached as well to reduce LDAP interaction, but it is not used for identification. If the DN changes, the changes will be found. The internal username is used all over. Clearing the mappings will have leftovers everywhere. Clearing the mappings is not configuration sensitive, it affects all LDAP configurations! Never clear the mappings in a production environment, only in a testing or experimental stage." : "שמות משתמש משמשים לאחסון ושיוך (מטא-דטה) מידע. במטרה לזהוי מדוייק של משתמשים, לכל משתמש LDAP יהיה שם משתמש פנימי. זה מחייב מיפוי משם משתמש למשתמש LDAP. שם המשתמש שנוצר ממופה ל- UUID של משתמש ה- LDAP. בנוסף ה- DN נשמר גם הוא בזכרון המטמון להפחתת פעולת גומלין LDAP, אבל זה אינו משמש לזיהוי. אם ה- DN משתנה, השינויים יאותרו. שם המשתמש הפנימי משמש לכל הרוחב. ניקוי המיפויים ישאיר שאריות בכל מקום. ניקוי המיפויים אינו הגדרה רגישה, הוא משפיע על כל הגדרות ה-LDAP! אין לנקות את המיפויים בסביבה פעילה, רק בסביבת בדיקה או ניסוי.",
    "Clear Username-LDAP User Mapping" : "ניקוי מיפוי שם משתמש LDAP:",
    "Clear Groupname-LDAP Group Mapping" : "ניקוי מיפוי שם משתמש קבוצה LDAP:"
},
"nplurals=4; plural=(n == 1 && n % 1 == 0) ? 0 : (n == 2 && n % 1 == 0) ? 1: (n % 10 == 0 && n % 1 == 0 && n > 10) ? 2 : 3;");
