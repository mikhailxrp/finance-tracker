# Dev Log — Finance Tracker

## Формат записи

**Дата:** ДД.ММ.ГГГГ
**Что сделано:** ...
**Что следующее:** ...

---

**Дата:** 20.04.2026

**Что сделано (Phase 2, TASK.md — Таск 2.1 «Разделение на отдельные страницы income/expenses»):**

- Обновлены маршруты в `config/routes.php`: добавлены `GET /income`, `GET /expenses`, `POST /income`, `POST /expenses`; `GET /transactions` ведёт на `/income`.
- Существенно обновлён `src/Controllers/TransactionController.php`: добавлены методы `income()`, `expenses()`, `storeIncome()`, `storeExpense()`, общая обработка по типу транзакции (`income`/`expense`), сохранён PRG, проверки CSRF и изоляция данных по `user_id`.
- Расширен `src/Models/Transaction.php`: добавлен `getRecentForUserByType()`; `findByIdForUser()` возвращает `category_type` для безопасной проверки операций edit/update/delete в рамках текущего раздела.
- Расширен `src/Models/Category.php`: добавлен `getForUserByType()` для фильтрации категорий по типу (`income`/`expense`) с сохранением дедупликации.
- Созданы страницы `src/Views/income.php` и `src/Views/expenses.php`; добавлена вкладочная навигация между разделами, отдельные заголовки и списки по типу.
- Добавлен переиспользуемый компонент `src/Views/components/transaction-form.php` (единая форма create/edit для обеих страниц).
- Обновлены `src/Views/components/transaction-row.php` и `src/Views/components/confirm-modal.php`: добавана передача `return_to`/`return` для корректного возврата в текущий раздел после редактирования и удаления.
- Создан `public/assets/css/main.css` и в него вынесены общие стили страниц транзакций; старый `public/assets/css/transactions.css` помечен как deprecated.
- Проведена консолидация CSS-переменных: все `:root` переменные перенесены в `public/assets/css/main.css`; удалены `:root` из `auth.css`, `header.css`, `form-submit.css`, `typography.css`, `transactions.css`.
- Убрано дублирование переменных и значений (включая повторяющиеся цвета вроде `#ececf1`, `#4f8ef7`, `#dc2626`): введены единые глобальные токены (`--color-*`, `--font-*`, `--header-*`, `--submit-loader-*`) и обновлены использования в стилях.
- Обновлены подключения стилей во view-файлах (`src/Views/auth/*.php`, `src/Views/dashboard.php`, `src/Views/transactions.php`, `src/Views/income.php`, `src/Views/expenses.php`): `main.css` подключается первым, чтобы все переменные были доступны в остальных CSS.

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 3:** агрегаты дашборда за всё время (`total_income`, `total_expense`, `balance`, `top_expense_category`) с изоляцией по текущему пользователю.

---

**Дата:** 19.04.2026

**Что сделано (Phase 0, после TASK.md — Таск 1 «Ядро логгера»):**

- Добавлен модуль `src/Core/Logger.php`: функции `log_write`, уровни `log_debug` / `log_info` / `log_warning` / `log_error`, `log_exception` для исключений (класс, сообщение, файл, строка, trace), единый формат строки `[ISO-8601] LEVEL сообщение | JSON-контекст`, запись в файл с блокировкой (`FILE_APPEND | LOCK_EX`), при сбое записи — fallback в `error_log()`.
- Фильтрация по минимальному уровню через `APP_LOG_LEVEL` (переменная окружения, по умолчанию `error`).
- Санитизация контекста: отбрасывание ключей с чувствительными фрагментами (`password`, `token`, `session` и т.д.), аккуратная сериализация вложенных массивов и `Throwable`.
- В `config/config.php`: константы `LOG_DIR` (по умолчанию `storage/logs`), `LOG_FILE` (`app.log`), `APP_LOG_LEVEL`; подключение логгера после определения констант.
- В `.gitignore` добавлены `storage/logs/*.log`; в репозитории оставлен каталог через `storage/logs/.gitkeep`.

**Что следующее (по [.docs/phases/phase-0.md](.docs/phases/phase-0.md)):**

- **Таск 2:** глобальные обработчики PHP (`set_exception_handler`, shutdown для фаталов и т.д.) и подключение в `public/index.php`, чтобы необработанные ошибки автоматически шли в этот логгер; обобщённый ответ пользователю в production.
- **Таск 3 (опционально):** endpoint + JS для клиентских ошибок.

---

**Дата:** 19.04.2026

**Что сделано (Phase 0, TASK.md — Таск 2 «Глобальные обработчики PHP и точка входа»):**

- Добавлен `src/Core/ErrorHandlers.php`: `register_app_error_handlers()` — `set_exception_handler` (лог через `log_exception`, ответ `text/plain`, детали только при `APP_ENV === 'local'`), `set_error_handler` (лог runtime-ошибок через `log_error` / `log_debug` для notice, возврат `false`), `register_shutdown_function` для фаталов (`E_ERROR`, `E_PARSE`, и т.д.) с `log_error` и обобщённым телом ответа вне local.
- Флаг `$GLOBALS['__app_uncaught_exception_handled']`, чтобы после обработанного исключения shutdown не дублировал вывод и повторный лог.
- `public/index.php`: подключение `ErrorHandlers.php` и вызов `register_app_error_handlers()` после конфига и `Router.php`, до `ensureSessionStarted()` и `dispatch()`.
- `src/Core/Router.php`: убран локальный `try/catch` в `dispatch()` — исключения из контроллеров обрабатываются глобальным обработчиком и попадают в логгер.
- `config/config.php` не менялся — политика «детали пользователю» завязана на существующий `APP_ENV`.

**Что следующее (по [.docs/phases/phase-0.md](.docs/phases/phase-0.md)):**

- **Таск 3 (опционально):** endpoint приёма клиентских ошибок и JS (`window.onerror` / `unhandledrejection`).

---

**Дата:** 19.04.2026 — Phase 0 завершена

- Реализован логгер ошибок (Logger.php, ErrorHandlers.php)
- Глобальные обработчики PHP подключены в index.php
- Таск 3 (JS ошибки) осознанно отложен до Phase 3

**Детали:** .docs/phases/phase-0-report.md

---

**Дата:** 19.04.2026

**Что сделано (Phase 1, TASK.md — Таск 2 «Подтверждение email через SMTP Yandex»):**

- **`.env`:** в комментарии перечислены имена переменных SMTP (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM`, `MAIL_FROM_NAME`, `MAIL_ENCRYPTION`); секреты только в окружении.
- **`database/install.php`:** поля `email_verified_at`, `email_token` в `users` (в DDL и идемпотентная миграция через `INFORMATION_SCHEMA`); после миграции — `UPDATE` для уже существующих пользователей без токена (помечаются как подтверждённые).
- **`src/Services/MailService.php`:** отправка писем по SMTP (режимы `ssl` и `tls`), настройки из `.env`, логирование ошибок через существующий логгер.
- **`src/Controllers/AuthController.php`:** после регистрации — генерация токена (срок ~48 ч, в БД хранится SHA-256), письмо со ссылкой, без автоматического входа; сессия `pending_verification_user_id`; вход заблокирован до `email_verified_at`; при верном пароле у неподтверждённого — редирект на `/verify-email`; обработка ссылки, повторная отправка (`POST`), понятные сообщения для просроченного/неверного токена.
- **`config/routes.php`:** `GET /verify-email`, `GET /verify-email/{token}`, `POST /verify-email/resend`.
- **`src/Views/auth/verify-email.php`:** страница «Проверьте почту», форма повторной отправки с CSRF из сессии.
- **`src/Views/auth/verified.php`:** страница «Email подтверждён» (в т.ч. если ссылка использована повторно).

**Что следующее (по [.docs/phases/phase-1.md](.docs/phases/phase-1.md)):**

- Phase 1 по продукту: пользователь может регистрироваться, подтвердить email и войти — после проверки DoD можно закрыть фазу и обновить `_status.md` / отчёт при необходимости.

---

**Дата:** 20.04.2026

**Что сделано (независимая задача, TASK.md — «Забыли пароль?»):**

- Реализован flow восстановления пароля: `GET/POST /forgot-password`, `GET /reset-password/{token}`, `POST /reset-password` в `config/routes.php` и `src/Controllers/AuthController.php`.
- Добавлены страницы `src/Views/auth/forgot-password.php` и `src/Views/auth/reset-password.php`; в `src/Views/auth/login.php` ссылка «Забыли пароль?» переведена с `#` на `/forgot-password`.
- Добавлена отправка письма для сброса пароля через существующий `MailService`: одноразовая ссылка с токеном.
- Реализована безопасность: neutral response (без раскрытия существования email), TTL токена 1 час, удаление токена после успешного сброса.
- Добавлен rate limit: повторный запрос сброса в пределах 5 минут не создаёт новую отправку.
- Таблица `password_resets` добавлена в `database/install.php` по образцу существующих таблиц; временный файл `.docs/migrations/002_password_resets.sql` удалён.

**Что следующее:**

- Прогнать smoke-проверку в браузере по чек-листу из `TASK.md` на стенде с реальным SMTP.

---

**Дата:** 20.04.2026

**Что сделано (Phase 2, TASK.md — Таск 1 «База транзакций: список + создание»):**

- Добавлены маршруты в `config/routes.php`: `GET /transactions` → список и форма, `POST /transactions` → создание транзакции.
- Реализован `src/Controllers/TransactionController.php`: метод `index()` для отображения страницы, `store()` для сохранения с серверной валидацией (сумма > 0, корректная дата не из будущего, категория доступна пользователю), CSRF-защита, flash-уведомления, PRG-паттерн после POST.
- Добавлена модель `src/Models/Transaction.php`: `getRecentForUser()` (последние транзакции пользователя с JOIN категорий), `create()` (сохранение новой записи через PDO prepared statements).
- Добавлена модель `src/Models/Category.php`: `getForUser()` (категории пользователя + системные с дедупликацией).
- Добавлены views: `src/Views/transactions.php` (страница со списком и формой), `src/Views/components/transaction-row.php` (компонент строки транзакции с отображением даты, категории, комментария, суммы с разделением income/expense).
- Добавлены стили `public/assets/css/transactions.css` (форма, список, responsive grid, цветовая индикация доходов/расходов).
- Все стили проекта перенесены из `public/css/` в `public/assets/css/` (auth, header, typography, transactions), обновлены пути во всех шаблонах.
- Исправлен баг дублирования категорий в селекте.
- Добавлена валидация даты: запрещены будущие даты транзакций (сравнение строк дат для корректной работы с текущим днём).

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 2:** редактирование и удаление транзакций с проверкой доступа.

---

**Дата:** 20.04.2026

**Что сделано (Phase 2, TASK.md — Таск 2.1 Разделение на отдельные страницы income/expenses:**

- Обновлены маршруты в `config/routes.php`: добавлены `POST /transaction/{id}/update` и `POST /transaction/{id}/delete`.
- Расширен `src/Models/Transaction.php`: добавлены методы `findByIdForUser()`, `updateForUser()`, `deleteForUser()` с обязательной изоляцией по `user_id`.
- Обновлён `src/Controllers/TransactionController.php`: реализованы `edit()`, `update()`, `delete()`; добавлены проверки CSRF, валидация входных данных, проверка доступа к записи, flash-уведомления и PRG после POST.
- Обновлён `src/Views/transactions.php`: форма переведена в режим create/edit (префилл данных при редактировании, кнопка отмены, корректные action для update).
- Обновлён `src/Views/components/transaction-row.php`: добавлены действия редактирования/удаления в строке транзакции.
- Добавлен `src/Views/components/confirm-modal.php`: модальное подтверждение удаления (Bootstrap) с POST-формой и CSRF-токеном.
- Обновлены стили `public/assets/css/transactions.css`: стили действий строки, адаптив, приведение внешнего вида кнопок действий к макету (иконки edit/delete без рамок, компактный UI справа от суммы).

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 3:** Агрегации дашборда (без фильтра, за всё время)

---

**Дата:** 20.04.2026

**Что сделано (Phase 2, TASK.md — Таск 3 «Агрегации дашборда (без фильтра, за всё время)»):**

- Расширен `src/Models/Transaction.php`: добавлены методы `getDashboardTotalsForUser()` (агрегирует `total_income`, `total_expense`, вычисляет `balance` через `CASE WHEN` и `COALESCE`) и `getTopExpenseCategoryForUser()` (находит топ-категорию расходов с `SUM()` и `ORDER BY amount DESC`), все агрегации с изоляцией по `user_id` через PDO prepared statements.
- Обновлён `src/Controllers/DashboardController.php`: убрана заглушка, добавлена загрузка агрегатов дашборда (`totals`, `topExpenseCategory`, `recentTransactions`), обработка ошибок через `try/catch` с `log_exception()`, передача данных во view с защитой от падения при пустой БД.
- Обновлён `src/Views/dashboard.php`: добавлены 4 карточки метрик (доходы, расходы, баланс, топ-категория расходов) через Bootstrap grid, секция «Последние транзакции» с переиспользованием компонента `transaction-row.php` (5 последних записей), ссылка «Все транзакции →» на `/income`, кнопка «Выйти» с формой POST `/logout`, все данные экранируются через `htmlspecialchars()`.
- Создан `src/Views/components/stat-card.php`: переиспользуемый компонент карточки метрики с заголовком, значением и описанием.
- Обновлён `public/assets/css/main.css`: добавлены стили `.transactions-list__header` (flex-контейнер для заголовка и ссылки), `.transactions-link` (стиль ссылки «Все транзакции →»), исправлены отсутствующие CSS-переменные `--transactions-brand-start` и `--transactions-brand-end` (добавлены алиасы `--color-brand-start/end` для корректного отображения градиентов кнопок).
- Добавлена навигация на страницах `/income` и `/expenses`: таб «← Дашборд» в `.transactions-tabs` для возврата на `/dashboard`.
- Все изменения в рамках scope, без рефакторинга существующего кода, с полной изоляцией по `user_id`, без SQL в Controller/View.

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 4:** Фильтры периода (week/month/year) + интеграция в агрегаты

---

**Дата:** 20.04.2026

**Что сделано (Phase 2, TASK.md — Таск 5 «Графики ApexCharts на дашборде»):**

- Расширен `src/Models/Transaction.php`: `getExpensesByCategoryForPeriod()` (топ-категории расходов с суммами, процентами и цветами) и `getDailyDynamicsForPeriod()` (динамика доходов/расходов по дням, с заполнением пустых дней нулями).
- Обновлён `src/Controllers/DashboardController.php`: сбор `chartData` (expense_categories, daily_dynamics) и передача во view; обработка случаев с пустыми данными.
- Обновлён `src/Views/dashboard.php`: два блока графиков в отдельных `section.transactions-card`-контейнерах внутри `div.dashboard-charts` (flex-layout), передача `chartData` в `window.chartData` через `json_encode()`, подключение ApexCharts CDN и `public/assets/js/charts.js`.
- Создан `public/assets/js/charts.js`: donut-график расходов по категориям и area-график динамики доходов/расходов через ApexCharts; декларативные `responsive` breakpoints, `requestAnimationFrame` для корректной инициализации.
- Решение о библиотеке: начали с Chart.js, столкнулись с проблемами responsive (без перезагрузки страницы layout не пересчитывался корректно, легенда donut вызывала горизонтальный overflow). Перешли на ApexCharts — стабильнее, декларативные breakpoints, легенда снизу по умолчанию.
- Обновлён `public/assets/css/main.css`: `.dashboard-charts` переведён с grid на flex (1.5fr:1fr на десктопе, column на мобиле); исправлен overflow в `.transactions-main` через `grid-template-columns: minmax(0, 1fr)`; `.transactions-item` переписан с grid на flex с точечными breakpoints (`<=768px` — column, `769–1100px` — сжатый row); `.transactions-list__header` адаптивен (`<=768px` — column). Добавлено `overflow-x: hidden` на `body.transactions-page`.
- Обновлён `public/assets/css/header.css`: в диапазоне `320–576px` header центрируется (`justify-content: center`).

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 5.1:** Редизайн страницы `/income` по макету `.design/income-page.PNG` (stat-карточки, bar-график по источникам, группировка списка по месяцам, двухколоночный layout).
- **Таск 5.2:** Редизайн страницы `/expenses` по макету `.design/expenses-page.PNG` (аналогичная структура, статистика и график по расходам).

---

**Дата:** 20.04.2026

**Что сделано (Phase 2, TASK.md — Таск 4 «Фильтры периода (week/month/year) + интеграция»):**

- Обновлён `src/Models/Transaction.php`: добавлена единая логика периода (`week/month/year`, дефолт `month`) и расчёт границ дат в одном месте; период подключён к агрегатам дашборда (`getDashboardTotalsForUser()`, `getTopExpenseCategoryForUser()`) и выборкам транзакций (`getRecentForUser()`, `getRecentForUserByType()`).
- Обновлён `src/Controllers/DashboardController.php`: чтение `period` из query-параметра, нормализация периода, передача периода в Model и View (`selectedPeriod`, `currentPath`), сохранена обработка ошибок через `try/catch`.
- Обновлён `src/Controllers/TransactionController.php`: чтение и нормализация `period` для страниц `/income` и `/expenses`, передача периода в выборку списка и в view.
- Обновлён `src/Views/components/header.php`: переключатель периода вынесен в header и сделан рабочим через ссылки с `?period=...`; подписи переведены на русский (`Неделя`, `Месяц`, `Год`), активный период подсвечивается.
- Обновлён `src/Views/dashboard.php`: удалён дублирующий переключатель периода под заголовком, данные и подписи карточек работают от выбранного периода, ссылка на список транзакций сохраняет текущий период.
- Обновлены `src/Views/income.php` и `src/Views/expenses.php`: удалены дублирующие переключатели периода в теле страниц; навигационные ссылки сохраняют `period` в URL.
- Проверено `php -l` для изменённых файлов — синтаксических ошибок нет; линтер-ошибок по изменённым файлам нет.

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 5:** Графики Chart.js (категории + динамика)

---

**Дата:** 21.04.2026

**Что сделано (Phase 2, TASK.md — Таск 5.1 «Редизайн страницы `/income` по макету»):**

- Обновлён `src/Controllers/TransactionController.php` (ветка `income`): добавлен сбор и передача во view `statsData`, `chartData`, `transactionsByMonth`; добавлено форматирование месяцев для графика (`Янв`, `Фев`, ...) и для групп списка (`Июнь 2026`).
- Расширен `src/Models/Transaction.php`: реализованы методы `getIncomeStatsForUser()` (итого за период, основной источник, средний доход в месяц), `getIncomeByCategoryMonthlyForUser()` (series по категориям в разрезе месяцев), `getIncomeGroupedByMonthForUser()` (группировка транзакций по месяцам).
- Перевёрстана `src/Views/income.php` по референсу `.design/income-page.PNG`: верхний ряд из 3 stat-карточек, блок формы «Добавить доход», ниже двухколоночный блок (bar-график слева + список справа с группировкой по месяцам), передача данных графика через `json_encode()` в `window.incomeChartData`.
- Обновлён `src/Views/components/stat-card.php`: добавлен вариант с иконкой (через `statIconName`) без поломки существующих вызовов компонента.
- Обновлены `src/Views/components/transaction-row.php` и `src/Views/components/transaction-form.php`: добавлен компактный вариант строки для правой колонки income-страницы и вариант формы для макета `/income`.
- Создан `public/assets/js/income-charts.js`: инициализация bar-графика ApexCharts (series = категории, xaxis = месяцы, легенда снизу) с корректным empty-state.
- Обновлён `public/assets/css/main.css`: стили layout и компонентов под референс (`income-stats`, `income-layout`, compact-строки, адаптив 320px+), а также финальная правка по требованию — `.income-layout` переключается в `flex-direction: row` только с `min-width: 1440px`, до `1440px` остаётся `column`.
- Проверки: `php -l` для изменённых PHP-файлов и `ReadLints` по изменённым файлам — без ошибок.

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 5.2:** редизайн страницы `/expenses` по макету `.design/expenses-page.PNG`.
- **Таск 6:** приёмка фазы и стабилизация.

---

**Дата:** 21.04.2026

**Что сделано (Phase 2, TASK.md — Таск 5.2 «Редизайн страницы `/expenses` по макету»):**

- Обновлён `src/Models/Transaction.php`: добавлены методы `getExpenseStatsForUser()` (итого за период, основная категория, средние расходы в месяц) и `getExpensesGroupedByMonthForUser()` (группировка расходов по месяцам).
- Обновлён `src/Controllers/TransactionController.php` (ветка `expense` в `renderByType()`): добавлен сбор `statsData`, `topCategories` (реюз `getExpensesByCategoryForPeriod()`) и `transactionsByMonth`; добавлен нормализатор `normalizeExpenseStats()`.
- Перевёрстана `src/Views/expenses.php` по референсу `.design/expenses-page.PNG`: ряд из 3 stat-карточек, форма «Добавить расход», двухколоночный layout (слева блок «Топ трат», справа список с группировкой по месяцам), compact-вариант строки транзакции в правой колонке.
- Обновлён `src/Views/components/stat-card.php`: добавлены новые иконки `trend-down` и `category` без поломки существующих вариантов.
- Обновлён `public/assets/css/main.css`: стили для `expenses`-layout (`expenses-stats`, `expenses-layout`, `expenses-month-group`) и блока «Топ трат» (progress-list, адаптив 320px+, переключение в 2 колонки с `min-width: 1440px`).
- Дополнительно по UI-правкам после основного таска: возвращены табы навигации под header на `/income` и `/expenses`; для `.transactions-tabs` настроен адаптив (`column` до `425px`, `row` выше); на `dashboard` добавлены кнопки быстрых действий «Добавить расход» и «Добавить доход» (в стиле референса).
- Проверки: `php -l` по изменённым PHP-файлам и `ReadLints` по изменённым файлам — без ошибок.

**Что следующее (по [.docs/phases/phase-2.md](.docs/phases/phase-2.md)):**

- **Таск 6:** приёмка фазы и стабилизация.

---

**Дата:** 21.04.2026 — Phase 2 завершена

**Что сделано (Phase 2, TASK.md — Таск 6 «Приёмка фазы и стабилизация»):**

- Проведена финальная приёмка блока «Транзакции + Дашборд» по чек-листу `TASK.md`: CRUD, фильтры периода, агрегаты, графики, группировки списков и edge-cases.
- Проверено соответствие PRD для Phase 2 (`.docs/prd.md`, раздел roadmap + требования безопасности/изоляции данных).
- Подготовлен итоговый отчёт: создан `.docs/phases/phase-2-report.md` (цель, выполненные таски 1–6, ключевые решения, техдолг, перенос в Phase 3, метрики, итог).
- Обновлён статус фазы в `.docs/phases/phase-2.md`: Таск 6 помечен как `✅ Готов`, статус Phase 2 — `✅ Завершена`.
- Зафиксировано решение по техдолгу: дублирование методов группировки доходов/расходов не рефакторим в приёмке, переносим в Phase 3 по правилу трёх.

**Что следующее (по roadmap):**

- **Phase 3:** Цели и планирование (`GoalController`, `PurchasePlanController`, `CreditController`).

**Детали:** `.docs/phases/phase-2-report.md`

---

**Дата:** 21.04.2026

**Что сделано (Phase 3, TASK.md — Таск 1 «Цели: база (список + создание через модалку)»):**

- Добавлены маршруты в `config/routes.php`: `GET /savings` (страница целей) и `POST /savings/create` (создание цели).
- Реализованы `src/Models/Goal.php` и `src/Controllers/GoalController.php`: загрузка целей пользователя с изоляцией по `user_id`, создание новой цели, PRG, flash-сообщения, сохранение введённых данных формы при ошибке.
- Реализована серверная валидация в `GoalController`: `title` (required, trim, <=150), `target_amount > 0`, `period` в `week|month|year`, `deadline` в формате `Y-m-d` и не раньше сегодняшней даты.
- Добавлена CSRF-защита для создания целей: генерация токена в сессии, скрытое поле в форме, проверка токена в POST-обработчике.
- Созданы view и компоненты для блока целей: `src/Views/savings.php`, `src/Views/components/goal-card.php`, `goal-card-add.php`, `goal-form-modal.php`.
- Добавлен компонент быстрых действий `src/Views/components/quick-actions.php` и подключён на `/dashboard` и `/savings`; реализовано активное состояние кнопок и порядок кнопок по макету.
- По UI-правкам после реализации: на `/savings` добавлена кнопка «Дашборд» первой в блоке быстрых действий; на `/income` и `/expenses` убрано дублирование навигации (удалён лишний блок quick-actions).
- Добавлены `public/assets/css/savings.css` и `public/assets/js/savings.js` (layout целей на flex, стили модалки, открытие/закрытие модалки по кнопке, backdrop и Esc).
- Проверки: `ReadLints` по изменённым файлам — без ошибок; `php -l` для новых PHP-файлов — без синтаксических ошибок.

**Что следующее (по [.docs/phases/phase-3.md](.docs/phases/phase-3.md)):**

- **Таск 2:** редактирование цели, списание с баланса, статусы, sparkline и миграции (`goal_id` + категория «Накопления»).

---

**Дата:** 21.04.2026

**Что сделано (Phase 3, TASK.md — Таск 2 «Цели: редактирование, списание с баланса, статусы»):**

- Добавлены POST-маршруты в `config/routes.php`: `/savings/update`, `/savings/delete`, `/savings/contribute`, `/savings/status`.
- Расширен `src/Models/Goal.php`: реализованы `findForUser()`, `update()`, `delete()`, `setStatus()`, `addContribution()`; пополнение выполнено атомарно в DB-транзакции (`beginTransaction`/`commit`/`rollBack`) с автопереводом цели в `completed` при достижении `target_amount`.
- Расширен `src/Models/Category.php`: добавлен `getSavingsCategoryId()` для поиска системной категории «Накопления».
- Обновлён `src/Controllers/GoalController.php`: добавлены обработчики `update()`, `delete()`, `contribute()`, `setStatus()`; CSRF-проверка для всех POST-действий; проверка владения целью (`user_id`) с ответом 404 для чужих целей; flash-ошибки и сохранение old input.
- Добавлена модалка редактирования `src/Views/components/goal-edit-modal.php` и обновлена карточка цели `src/Views/components/goal-card.php`: действия редактирования/удаления/смены статуса, форма пополнения с CSRF и `goal_id`, визуальные состояния `completed`/`cancelled`.
- Обновлены UI-стили в `public/assets/css/savings.css`: карточки целей приведены к общему дизайну проекта (типографика, кнопки, тени, адаптив на flex без grid).
- По уточнению требований sparkline из карточки цели удалён полностью: удалены контейнер в `goal-card.php`, подключение ApexCharts и `goal-sparklines.js` в `savings.php`, удалён `public/assets/js/goal-sparklines.js`, убраны связанные стили и backend-логика.
- Обновлены документы: `.docs/phases/phase-3.md` (добавлен Таск 2.5 про возврат средств при отмене/удалении цели, обновлён статус Таска 2), `TASK.md` синхронизирован с фактическим scope (без sparkline).
- Проверки: `php -l` по изменённым PHP-файлам и `ReadLints` по изменённым файлам — без ошибок.

**Что следующее (по [.docs/phases/phase-3.md](.docs/phases/phase-3.md)):**

- **Таск 2.5:** возврат средств на баланс при отмене/удалении цели (модалка выбора «вернуть / не возвращать»).

---

**Дата:** 21.04.2026

**Что сделано (Phase 3, TASK.md — Таск 2.5 «Возврат средств на баланс при отмене/удалении цели»):**

- Добавлен `src/Views/components/goal-confirm-modal.php`: модалка с текстом действия, суммой накопленного, скрытыми полями `csrf`, `goal_id`, `return_funds`, `status` (для отмены); форма без `data-submit-loading`, чтобы не конфликтовать с отправкой `return_funds`.
- Обновлён `public/assets/js/savings.js`: открытие модалки по `data-goal-confirm-open` с подстановкой `action` (`/savings/delete` или `/savings/status`), переключение поля `status` (для удаления имя снимается, чтобы не уходило лишнее поле); отправка через скрытый `return_funds` (`1` / `0`) и `form.submit()` по кнопкам `type="button"` — обход проблемы, когда `form-submit.js` отключал named submit-кнопки до отправки и `return_funds` не попадал в POST.
- Расширен `src/Models/Goal.php`: `removeContributions()`; `delete(..., bool $returnFunds)` — при возврате средств транзакция БД: удаление транзакций с `goal_id`, затем удаление цели; `setStatus(..., bool $returnFunds)` — при отмене с возвратом: удаление транзакций, `current_amount = 0`, `status = cancelled` в одной транзакции; после успешного commit для ветки «отмена + вернуть» возвращается `true` без зависимости от `rowCount()`.
- Обновлён `src/Controllers/GoalController.php`: разбор `return_funds` в `delete()` (строго `'0'`/`'1'`) и в `setStatus()` при `status = cancelled` (`parseReturnFundsForCancel`, пустое значение — как «не возвращать»).
- Обновлены `src/Views/components/goal-card.php` и `src/Views/savings.php`: триггеры с `data-goal-*` для активных целей; для **отменённой** цели «Удалить» — прямая POST-форма с `return_funds=0` без модалки (средства уже обработаны при отмене); на `<body>` добавлен `data-savings-period` для построения URL в JS.
- Дополнены стили в `public/assets/css/savings.css` для блока подтверждения (flex, две кнопки-стратегии).
- Проверки по `TASK.md` DoD: логика сценариев удаления/отмены с возвратом и без, CSRF, изоляция по `user_id`, PDO prepared statements, экранирование во views; `php -l` и линтер — без ошибок.

**Что следующее (по [.docs/phases/phase-3.md](.docs/phases/phase-3.md)):**

- **Таск 3:** кредиты — CRUD + калькулятор аннуитета.

---

**Дата:** 21.04.2026

**Что сделано (Phase 3, TASK.md — Таск 3 «Кредиты: CRUD + три сценария погашения (аннуитет)»):**

- Добавлен `src/Services/CreditCalc.php`: расчёт аннуитета `calculateAnnuity()` → `monthly_payment`, `total_paid`, `overpayment`; при ставке 0 — платёж `total/months`, переплата 0; сроки сценариев только через константы `TERM_AGGRESSIVE` (12), `TERM_OPTIMAL` (36), `TERM_MINIMAL` (60).
- Добавлен `src/Models/Credit.php`: CRUD с изоляцией по `user_id` — `getAllForUser()`, `findForUser()`, `create()`, `update()`, `delete()`, `setStatus()`; все запросы через PDO prepared statements.
- Добавлен `src/Controllers/CreditController.php`: `index()`, `create()`, `update()`, `delete()`, `close()` — `requireAuth`, валидация полей (как в TASK), CSRF на POST, PRG через `redirect()`, 404 при операциях с чужим `credit_id`; для активных кредитов на лету считаются три сценария погашения через `CreditCalc`.
- Добавлены страница и компоненты: `src/Views/credits.php` (активные / закрытые, модалки создания и редактирования), `src/Views/components/credit-card.php`, `src/Views/components/strategy-card.php`; вывод пользовательских данных через `htmlspecialchars()`.
- Добавлены `public/assets/css/credits.css` (flex, без grid; адаптив в одну колонку на узком экране) и `public/assets/js/credits.js` (модалки; автооткрытие модалки редактирования при ошибке валидации по `data-credit-edit-error`).
- Обновлены `config/routes.php` (GET `/credits`, POST `/credits/create|update|delete|close`), `src/Views/components/quick-actions.php` — пункт «Кредиты»; добавлен `src/Views/layout/header.php` как обёртка над `components/header.php`, страница кредитов подключает шапку через него.
- Стили модалок переиспользуют классы `goal-modal` / `goal-form` из `savings.css` (подключение на странице кредитов).

**Что следующее (по [.docs/phases/phase-3.md](.docs/phases/phase-3.md)):**

- **Таск 4:** планировщик покупки на `/savings` — CRUD + три стратегии накопления.

---

**Дата:** 22.04.2026

**Что сделано (Phase 3, TASK.md — Таск 4.1 «Добавить в цели» / конвертация плана покупки в цель):**

- В `config/routes.php` добавлен маршрут `POST /purchase-plans/convert` → `PurchasePlanController::convert()`.
- В `src/Controllers/PurchasePlanController.php` реализован `convert()`: POST `plan_id` + CSRF (`goals_csrf`); проверка владения планом через модель; создание цели и удаление плана в **одной транзакции БД** (`beginTransaction` / `commit` / `rollBack`); при отсутствии плана у пользователя — **404**; успех — редирект на `/savings?period=…` с flash `goals_form_notice`: «Цель добавлена»; при ошибке — flash ошибки плана покупок, план не удаляется.
- В `src/Models/PurchasePlan.php` добавлен `findForUser(PDO, int $planId, int $userId)` — выборка плана с фильтром по владельцу.
- В `src/Models/Goal.php` добавлен `createFromPlan(PDO, array $data)` — обёртка над `create()`: `period = 'month'`, `deadline = сегодня + term_months`, статус и `current_amount = 0` как в существующем `create()`.
- В `src/Views/components/purchase-plan-card.php` добавлена форма «Добавить в цели» (скрытые `csrf`, `plan_id`), рядом с удалением; обёртка `d-flex flex-wrap gap-2` (Bootstrap), у формы — `data-submit-loading` как у удаления.
- Проверки: `php -l` по затронутым PHP-файлам — без ошибок.

**Что следующее (по [.docs/phases/phase-3.md](.docs/phases/phase-3.md)):**

- Закрытие оставшихся пунктов **Таска 4** (если ещё не закрыты) и переход к **Таску 5** — приёмка фазы и отчёт.

---

**Дата:** 22.04.2026

**Сводка: Phase 3 — Таск 4 целиком («Планировщик покупки» на `/savings`, CRUD + три стратегии + перенос в цели)**

Ниже — что реализовано в коде по охвату Таска 4 (включая подтаск 4.1). Отдельная запись про 4.1 выше сохраняет детализацию конвертации.

- **Маршруты** (`config/routes.php`): `POST /purchase-plans/create`, `POST /purchase-plans/delete`, `POST /purchase-plans/convert` — отдельной страницы `/purchase-plans` нет, после POST — PRG на `/savings?period=…`.
- **Модель `src/Models/PurchasePlan.php`**: список планов пользователя, создание, выборка `findForUser` по владельцу, удаление — всё через PDO prepared statements и изоляцию по `user_id`.
- **Модель `src/Models/Transaction.php`**: для аналитики по периоду добавлены `getAverageMonthlyIncome()` / `getAverageMonthlyExpense()` (усреднение за заданное число месяцев).
- **Сервис `src/Services/PurchaseStrategy.php`**: расчёт трёх сценариев (Быстро / Умеренно / Разумно) от суммы покупки и выбранного пользователем базового срока `term_months` — множители срока (`MULTIPLIER_FAST` / `MODERATE` / `CAREFUL`), итоговый срок сценария, ежемесячная сумма, дата достижения; допустимые сроки в `AVAILABLE_TERMS`.
- **`src/Controllers/PurchasePlanController.php`**: `create()` и `delete()` с CSRF (`goals_csrf`), валидация названия/суммы/срока, flash и old input для формы плана; `convert()` — перенос плана в цель в одной DB-транзакции (см. запись «Таск 4.1»).
- **`src/Controllers/GoalController.php`**: в `index()` подгружаются планы пользователя, подготовка данных для карточек и маппинг стратегий в формат `strategy-card`, передача ошибок/старого ввода формы плана.
- **Интеграция с целями (`src/Models/Goal.php`)**: метод `createFromPlan()` — создание цели с `period = month`, дедлайном «сегодня + term_months» и статусом/начальным остатком как у обычного `create()`.
- **Интерфейс**: `src/Views/savings.php` — блок «Планировщик покупки» под целями (форма: название, стоимость, срок из `AVAILABLE_TERMS`, CSRF); список карточек планов; подключён `public/assets/css/purchase-plans.css`.
- **Компоненты**: `src/Views/components/purchase-plan-card.php` — три стратегии через переиспользуемый `strategy-card.php`, формы удаления и «Добавить в цели» (Bootstrap `d-flex` для кнопок).
- **Безопасность и качество**: CSRF на POST-формах планировщика и конвертации, экранирование во views, отсутствие отдельного SQL в контроллерах; layout списка/карточек — flex (без grid), в духе `CLAUDE.md`.

**Замечание:** в `.docs/phases/phase-3.md` для Таска 4 описан вариант с `free_cash` и процентами от него; **в текущей реализации** стратегии строятся от суммы покупки и желаемого срока через `PurchaseStrategy::calculate()`, без подстановки `free_cash` в карточку (методы усреднения в `Transaction` остаются доступными для данных/будущих доработок).

**Что следующее (по [.docs/phases/phase-3.md](.docs/phases/phase-3.md)):**

- **Таск 5** — приёмка фазы, отчёт, обновление служебных документов.

---

**Дата:** 22.04.2026 — Phase 3 завершена

**Что сделано (Phase 3, TASK.md — Таск 5 «Приёмка фазы и отчёт»):**

- Проведена финальная проверка всех функций фазы (цели, кредиты, планировщик) по критериям DoD.
- Проверены все сценарии: создание/пополнение/отмена целей с возвратом средств, калькулятор аннуитета с `rate = 0` и `rate > 0`, конвертация плана в цель.
- Проверены регрессии Phase 1-2: авторизация, транзакции, дашборд, графики — работают без ошибок.
- Технические критерии: CSRF на всех POST-формах Phase 3 (auth-формы остаются без CSRF как в оригинальной реализации Phase 0-1), PDO prepared statements (кроме одного `$pdo->query()` в `AuthController` для статической выборки системных категорий), экранирование переменных через `htmlspecialchars()` в views.
- Обновлены документы:
  - `TASK.md` — финальный статус Таска 5
  - `.docs/phases/phase-3.md` — статус фазы изменён на ✅ Завершена, статус Таска 5 → ✅ Готово
  - `.docs/phases/phase-3-report.md` — создан отчёт по структуре `phase-2-report.md`: цель, 6 тасков, затронутые файлы (54 позиции), ключевые решения (атомарные операции, возврат средств, калькулятор), техдолг, метрики, DoD.
  - `dev-log.md` — эта запись о завершении фазы.

**Метрики Phase 3:**

- Закрыто тасков: **6/6** (включая промежуточный таск 2.5).
- Реализовано маршрутов: **13** (3 GET + 10 POST).
- Создано моделей: **3** (`Goal`, `Credit`, `PurchasePlan`).
- Создано компонентов view: **9**.
- Миграции БД: **2** (системная категория «Накопления», `goal_id` в `transactions`).
- Все layout — `display: flex`, без CSS Grid.

**Что следующее:**

- **Phase 4:** AI-аналитика — интеграция n8n webhook, рекомендации по целям и стратегиям, визуализация инсайтов.
