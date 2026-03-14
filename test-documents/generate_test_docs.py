#!/usr/bin/env python3
"""
Generate realistic test documents for VisaCRM testing.
All documents on behalf of Pulat Khaliulin.
Font: Arial Unicode (supports Cyrillic).
"""
import os
from datetime import datetime
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.lib.colors import black, gray, darkblue, HexColor
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

OUT = os.path.dirname(os.path.abspath(__file__))
W, H = A4

# Register fonts
pdfmetrics.registerFont(TTFont('F', '/Library/Fonts/Arial Unicode.ttf'))
pdfmetrics.registerFont(TTFont('FB', '/System/Library/Fonts/Supplemental/Arial Bold.ttf'))

P = {
    'fio': 'ХАЛИУЛИН ПУЛАТ РУСТАМОВИЧ',
    'fio_en': 'KHALIULIN PULAT',
    'first': 'PULAT', 'last': 'KHALIULIN',
    'dob': '15.03.1990',
    'pnum': 'AC1234567',
    'piss': '01.06.2020', 'pexp': '01.06.2030',
    'addr': 'г. Ташкент, Юнусабадский р-н, ул. Амира Темура, д. 45, кв. 12',
    'phone': '+998 97 155 00 27',
    'email': 'pulat@visabor.uz',
    'company': 'ООО "VISABOR TECHNOLOGY"',
    'position': 'Генеральный директор',
    'inn': '123456789',
    'wife': 'Халиулина Нигора Бахтиёровна',
    'son': 'Халиулин Артур Пулатович',
    'daughter': 'Халиулина Сабрина Пулатовна',
}
TODAY = datetime.now().strftime('%d.%m.%Y')
SUFFIX = ' — тест Пулат Халиулин'


def pdf(filename, draw_fn):
    path = os.path.join(OUT, filename)
    c = canvas.Canvas(path, pagesize=A4)
    draw_fn(c)
    c.save()
    print(f'  + {filename}')


def hdr(c, title, sub='', y=780):
    c.setFont('FB', 13)
    c.drawCentredString(W/2, y, title)
    if sub:
        c.setFont('F', 9)
        c.drawCentredString(W/2, y-16, sub)
    return y - 38


def line(c, x, y, text, font='F', size=10):
    c.setFont(font, size)
    c.drawString(x, y, text)
    return y - 15


def lines(c, x, y, texts, font='F', size=10, spacing=15):
    for t in texts:
        c.setFont(font, size)
        c.drawString(x, y, t)
        y -= spacing
    return y


def stamp(c, x, y, t1='ПЕЧАТЬ', t2=''):
    c.saveState()
    c.setStrokeColor(darkblue)
    c.setLineWidth(2)
    c.circle(x, y, 28)
    c.circle(x, y, 24)
    c.setFont('F', 7)
    c.setFillColor(darkblue)
    c.drawCentredString(x, y+6, t1)
    c.drawCentredString(x, y-5, t2 or 'УТВЕРЖДЕНО')
    c.restoreState()


def separator(c, y, x1=50, x2=545):
    c.setStrokeColor(gray)
    c.setLineWidth(0.5)
    c.line(x1, y, x2, y)
    c.setStrokeColor(black)
    c.setLineWidth(1)
    return y - 10


# =====================================================
# 1. Справка об остатке на счёте
# =====================================================
def bank_balance(c):
    y = hdr(c, 'НАЦИОНАЛЬНЫЙ БАНК УЗБЕКИСТАНА', 'Ташкентский филиал, ул. Навои 7А')
    line(c, 400, y+28, f'Дата: {TODAY}', size=9)
    line(c, 400, y+16, 'Исх. № НБУ-2026/1847', size=9)

    c.setFont('FB', 12)
    c.drawCentredString(W/2, y, 'СПРАВКА ОБ ОСТАТКЕ НА СЧЁТЕ')
    y -= 30

    y = lines(c, 50, y, [
        'Настоящим подтверждается, что клиент банка:',
        '',
        f'ФИО: {P["fio"]}',
        f'Дата рождения: {P["dob"]}',
        f'Паспорт: {P["pnum"]}',
        '',
        'является держателем следующих счетов:',
        '',
        'Счёт № 20208860123456789012 (USD)',
        f'Остаток на {TODAY}: $ 15,420.00 (пятнадцать тысяч четыреста двадцать долларов)',
        '',
        'Счёт № 20208860123456789013 (UZS)',
        f'Остаток на {TODAY}: 87,500,000 UZS',
        '(восемьдесят семь миллионов пятьсот тысяч сумов)',
        '',
        'Счёт открыт: 12.04.2018',
        'Клиент обслуживается с: 12.04.2018',
        '',
        'Справка выдана для предъявления по месту требования.',
        '',
        '',
        'Начальник филиала                              _______________ / Рахимов А.Б.',
        'Главный бухгалтер                              _______________ / Каримова Д.Н.',
    ])
    stamp(c, 150, y+25, 'НБУ', 'Ташкент')


# =====================================================
# 2. Сопроводительное письмо
# =====================================================
def cover_letter(c):
    y = 780
    line(c, 350, y, f'Ташкент, {TODAY}')
    y -= 25
    y = lines(c, 50, y, [
        'В Генеральное Консульство Франции',
        'г. Ташкент',
    ])
    y -= 10
    c.setFont('FB', 12)
    c.drawCentredString(W/2, y, 'СОПРОВОДИТЕЛЬНОЕ ПИСЬМО')
    y -= 25
    y = lines(c, 50, y, [
        'Уважаемые сотрудники Консульства,',
        '',
        f'Я, {P["fio"]}, паспорт {P["pnum"]},',
        'прошу рассмотреть мою заявку на получение краткосрочной визы категории С',
        '(туристическая) для посещения Французской Республики.',
        '',
        'Цель поездки: туризм, знакомство с культурой и историей Франции.',
        'Планируемые даты: 15.05.2026 — 29.05.2026 (14 дней).',
        'Маршрут: Париж — Лион — Ницца.',
        '',
        'Прилагаю полный комплект документов согласно требованиям консульства:',
        f'  — загранпаспорт (действителен до {P["pexp"]})',
        '  — справка с места работы',
        '  — справка из банка об остатке на счёте',
        '  — бронирование отеля и авиабилеты',
        '  — медицинская страховка',
        '  — фотографии 3.5 x 4.5 см',
        '',
        'Гарантирую возвращение в Республику Узбекистан по окончании поездки.',
        f'Являюсь генеральным директором {P["company"]},',
        'имею недвижимость и семью в Ташкенте.',
        '',
        'С уважением,',
        '',
        P["fio"],
        f'Тел: {P["phone"]}',
        f'Email: {P["email"]}',
    ])


# =====================================================
# 3. Бронирование отеля
# =====================================================
def hotel_booking(c):
    y = hdr(c, 'BOOKING CONFIRMATION', 'Booking.com — Confirmation Number: 3847291056')
    line(c, 400, y+28, 'Date: ' + datetime.now().strftime('%d %b %Y'), size=9)

    c.setFont('FB', 11)
    y = line(c, 50, y, 'Hotel: Mercure Paris Centre Tour Eiffel ****', 'FB', 11)
    y = line(c, 50, y, 'Address: 20 Rue Jean Rey, 75015 Paris, France')
    y -= 5

    for lbl, val in [
        ('Guest Name:', f'{P["first"]} {P["last"]}'),
        ('Check-in:', '15 May 2026, 14:00'),
        ('Check-out:', '22 May 2026, 11:00'),
        ('Duration:', '7 nights'),
        ('Room Type:', 'Superior Double Room, City View'),
        ('Rate:', 'EUR 145.00 per night'),
        ('Total:', 'EUR 1,015.00 (prepaid, non-refundable)'),
        ('Status:', 'CONFIRMED'),
        ('Payment:', 'Paid — Visa ending ****4521'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(200, y, val)
        y -= 16

    y -= 10
    y = separator(c, y)
    c.setFont('FB', 11)
    y = line(c, 50, y, 'Hotel: Ibis Nice Centre Gare ***', 'FB', 11)
    y = line(c, 50, y, 'Address: 7 Rue de Belgique, 06000 Nice, France')
    y -= 5

    for lbl, val in [
        ('Guest Name:', f'{P["first"]} {P["last"]}'),
        ('Check-in:', '22 May 2026, 14:00'),
        ('Check-out:', '29 May 2026, 11:00'),
        ('Duration:', '7 nights'),
        ('Total:', 'EUR 840.00 (prepaid)'),
        ('Status:', 'CONFIRMED'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(200, y, val)
        y -= 16

    c.setFont('F', 8)
    c.setFillColor(gray)
    c.drawString(50, y-10, 'This document serves as a confirmation of reservation. Booking.com acts as an intermediary.')
    c.setFillColor(black)


# =====================================================
# 4. Авиабилеты
# =====================================================
def air_tickets(c):
    y = hdr(c, 'ELECTRONIC TICKET / ITINERARY RECEIPT', 'Uzbekistan Airways — e-Ticket')
    c.setFont('F', 10)
    c.drawString(50, y, 'Booking Reference: HY7K3M')
    c.drawString(350, y, f'Date: {datetime.now().strftime("%d %b %Y")}')
    y -= 16
    c.drawString(50, y, f'Passenger: {P["last"]}/{P["first"]} MR')
    c.drawString(350, y, 'Ticket: 250-2468135790')
    y -= 25

    c.setFont('FB', 11)
    c.drawCentredString(W/2, y, '--- OUTBOUND ---')
    y -= 20
    for flt, dt, route, dep, arr, dur in [
        ('HY 055', '15 MAY 2026', 'TAS — IST', 'Tashkent 08:00', 'Istanbul 10:30', '3h 30m'),
        ('HY 5055*', '15 MAY 2026', 'IST — CDG', 'Istanbul 13:45', 'Paris CDG 16:30', '3h 45m'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, f'Flight: {flt}')
        c.drawString(200, y, dt)
        c.drawString(370, y, route)
        y -= 15
        c.setFont('F', 9)
        c.drawString(70, y, f'Departure: {dep}  →  Arrival: {arr}  ({dur})')
        y -= 22

    y -= 5
    c.setFont('FB', 11)
    c.drawCentredString(W/2, y, '--- RETURN ---')
    y -= 20
    for flt, dt, route, dep, arr, dur in [
        ('HY 056', '29 MAY 2026', 'NCE — IST', 'Nice 19:00', 'Istanbul 23:10', '3h 10m'),
        ('HY 5056*', '30 MAY 2026', 'IST — TAS', 'Istanbul 02:30', 'Tashkent 09:00', '4h 30m'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, f'Flight: {flt}')
        c.drawString(200, y, dt)
        c.drawString(370, y, route)
        y -= 15
        c.setFont('F', 9)
        c.drawString(70, y, f'Departure: {dep}  →  Arrival: {arr}  ({dur})')
        y -= 22

    y -= 5
    c.setFont('F', 10)
    c.drawString(50, y, 'Class: Economy (Y) | Baggage: 1 x 23 kg')
    y -= 15
    c.drawString(50, y, 'Total Fare: USD 687.00 | Status: CONFIRMED / TICKETED')


# =====================================================
# 5. Медицинская страховка
# =====================================================
def travel_insurance(c):
    y = hdr(c, 'СТРАХОВОЙ ПОЛИС / TRAVEL MEDICAL INSURANCE', 'INGO Uzbekistan Insurance Company')
    line(c, 400, y+28, 'Policy #: TM-2026-084712', size=9)

    y -= 5
    for lbl, val in [
        ('Застрахованный:', f'{P["fio"]}'),
        ('Дата рождения:', P['dob']),
        ('Паспорт:', P['pnum']),
        ('', ''),
        ('Территория:', 'Шенгенские страны + весь мир'),
        ('Период:', '15.05.2026 — 29.05.2026 (15 дней)'),
        ('Покрытие:', 'EUR 30,000 (медицинские расходы)'),
        ('', 'EUR 5,000 (потеря багажа)'),
        ('', 'EUR 2,000 (отмена поездки)'),
        ('', ''),
        ('Экстренный телефон:', '+998 71 120 60 60 (24/7)'),
        ('Ассистанс:', 'Global Voyager Assistance'),
        ('', ''),
        ('Страховая премия:', 'USD 28.00'),
        ('Статус:', 'ДЕЙСТВУЕТ'),
        ('Дата выдачи:', TODAY),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(220, y, val)
        y -= 16

    stamp(c, 460, y+30, 'INGO', 'INSURANCE')
    c.setFont('F', 8)
    c.setFillColor(gray)
    c.drawString(50, y-10, 'Полис соответствует требованиям для шенгенской визы (минимум EUR 30,000).')
    c.setFillColor(black)


# =====================================================
# 6. Маршрутный лист
# =====================================================
def itinerary(c):
    y = hdr(c, 'МАРШРУТНЫЙ ЛИСТ / TRAVEL ITINERARY')
    c.setFont('F', 10)
    c.drawString(50, y, f'Путешественник: {P["fio_en"]}')
    c.drawString(350, y, f'Паспорт: {P["pnum"]}')
    y -= 18
    c.drawString(50, y, 'Поездка: Франция | 15.05 — 29.05.2026 | 14 ночей')
    y -= 25

    days = [
        ('15 мая', 'TAS → CDG', 'Прилёт в Париж. Трансфер в отель Mercure Tour Eiffel.'),
        ('16 мая', 'Париж', 'Эйфелева башня, Марсово поле, круиз по Сене.'),
        ('17 мая', 'Париж', 'Лувр, сад Тюильри, Елисейские Поля.'),
        ('18 мая', 'Париж', 'Монмартр, Сакре-Кёр, район Маре.'),
        ('19 мая', 'Париж', 'Версальский дворец (дневная экскурсия).'),
        ('20 мая', 'Париж', 'Музей д\'Орсе, Латинский квартал, Нотр-Дам.'),
        ('21 мая', 'Париж', 'Свободный день. Шопинг, Galeries Lafayette.'),
        ('22 мая', 'Париж→Ницца', 'Поезд TGV в Ниццу. Заселение в Ibis Nice.'),
        ('23 мая', 'Ницца', 'Английская набережная, Старый город, Замковая гора.'),
        ('24 мая', 'Монако', 'Дневная поездка в Монако и Монте-Карло.'),
        ('25 мая', 'Ницца', 'Музей Матисса, Симье, Русский собор.'),
        ('26 мая', 'Канны', 'Дневная поездка в Канны и Антиб.'),
        ('27 мая', 'Эз', 'Деревня Эз, парфюмерная фабрика в Грассе.'),
        ('28 мая', 'Ницца', 'Пляж, рынок, сборы.'),
        ('29 мая', 'NCE → TAS', 'Вылет из Ниццы, обратный рейс через Стамбул.'),
    ]
    for dt, loc, desc in days:
        c.setFont('FB', 9)
        c.drawString(50, y, dt)
        c.setFont('F', 9)
        c.drawString(110, y, loc)
        c.drawString(200, y, desc)
        y -= 14

    y -= 8
    c.setFont('F', 10)
    c.drawString(50, y, 'Проживание: Mercure Paris (7 ночей) + Ibis Nice (7 ночей)')
    y -= 15
    c.drawString(50, y, 'Транспорт: Uzbekistan Airways (TAS-CDG, NCE-TAS), TGV Париж-Ницца')


# =====================================================
# 7. Налоговая декларация
# =====================================================
def tax_return(c):
    y = hdr(c, 'ГОСУДАРСТВЕННЫЙ НАЛОГОВЫЙ КОМИТЕТ', 'Республика Узбекистан — Юнусабадское РГНИ')
    line(c, 400, y+28, f'от {TODAY}', size=9)

    c.setFont('FB', 12)
    c.drawCentredString(W/2, y, 'СПРАВКА О ДОХОДАХ')
    c.setFont('F', 10)
    y -= 16
    c.drawCentredString(W/2, y, 'за 2025 год')
    y -= 25

    y = lines(c, 50, y, [
        f'Налогоплательщик: {P["fio"]}',
        f'ИНН: {P["inn"]}',
        f'Адрес регистрации: {P["addr"]}',
        '',
        f'Источник дохода: {P["company"]} (ИНН 309876543)',
        '',
        'Совокупный доход за 2025 год:',
        '',
        '  Заработная плата:              180,000,000 UZS',
        '  Дивиденды:                      45,000,000 UZS',
        '  Прочие доходы:                  12,500,000 UZS',
        '  ─────────────────────────────────────────',
        '  ИТОГО:                         237,500,000 UZS',
        '',
        '  Удержан НДФЛ (12%):             21,600,000 UZS',
        '  Социальные взносы:               7,200,000 UZS',
        '',
        'Справка выдана для предъявления в консульские учреждения.',
        '',
        '',
        'Начальник РГНИ             _______________ / Азимов С.Р.',
    ])
    stamp(c, 180, y+15, 'ГНИ', 'Юнусабад')


# =====================================================
# 8. Справка о несудимости
# =====================================================
def criminal_record(c):
    y = hdr(c, 'МИНИСТЕРСТВО ВНУТРЕННИХ ДЕЛ', 'Республика Узбекистан — ИИБ Юнусабадского района г. Ташкента')

    c.setFont('FB', 12)
    c.drawCentredString(W/2, y, 'СПРАВКА О НЕСУДИМОСТИ')
    y -= 22
    line(c, 400, y+10, f'№ СН-2026/0313-4782', size=9)
    line(c, 400, y-2, f'от {TODAY}', size=9)
    y -= 10

    y = lines(c, 50, y, [
        'Выдана гражданину:',
        '',
        f'ФИО: {P["fio"]}',
        f'Дата рождения: {P["dob"]}',
        'Место рождения: г. Ташкент, Республика Узбекистан',
        f'Паспорт: {P["pnum"]}, выдан {P["piss"]}',
        f'Адрес регистрации: {P["addr"]}',
        '',
        '',
        'По данным Информационного центра МВД Республики Узбекистан,',
        'указанный гражданин к уголовной ответственности НЕ ПРИВЛЕКАЛСЯ,',
        'судимости НЕ ИМЕЕТ.',
        '',
        '',
        'Справка действительна в течение 3 (трёх) месяцев со дня выдачи.',
        'Выдана для предъявления по месту требования.',
        '',
        '',
        'Начальник ИИБ                    _______________ / полковник Юсупов Б.А.',
    ])
    stamp(c, 160, y+15, 'МВД', 'РУз')


# =====================================================
# 9. Доказательства привязки к стране
# =====================================================
def proof_of_ties(c):
    y = hdr(c, 'ДОКАЗАТЕЛЬСТВА ПРИВЯЗКИ К СТРАНЕ ПРОЖИВАНИЯ')
    y = lines(c, 50, y, [
        f'ФИО: {P["fio"]}',
        f'Паспорт: {P["pnum"]}',
        '',
        '1. НЕДВИЖИМОСТЬ:',
        f'   Квартира: {P["addr"]}',
        '   Кадастровый номер: 10:03:0045:1234',
        '   Дата приобретения: 15.08.2019 | Площадь: 85 кв.м, 3 комнаты',
        '',
        '2. БИЗНЕС:',
        f'   {P["company"]} (ИНН 309876543)',
        f'   Доля: 100%, {P["position"]}',
        '   Дата регистрации: 01.09.2024 | Штат: 8 сотрудников',
        '',
        '3. СЕМЬЯ (все проживают в Ташкенте):',
        f'   Супруга: {P["wife"]} (1992 г.р.)',
        f'   Сын: {P["son"]} (2018 г.р.)',
        f'   Дочь: {P["daughter"]} (2021 г.р.)',
        '',
        '4. АВТОМОБИЛЬ:',
        '   Chevrolet Gentra, 2020 г.в., гос. номер: 01 A 123 BA',
        '',
        '5. ИСТОРИЯ ПУТЕШЕСТВИЙ:',
        '   Шенген (Испания) — июль 2023, возврат вовремя',
        '   Турция — октябрь 2022, возврат вовремя',
        '   ОАЭ — март 2024, возврат вовремя',
    ])


# =====================================================
# 10. Регистрация компании
# =====================================================
def company_reg(c):
    y = hdr(c, 'СВИДЕТЕЛЬСТВО О ГОСУДАРСТВЕННОЙ РЕГИСТРАЦИИ', 'юридического лица')
    line(c, 50, y, 'Министерство юстиции Республики Узбекистан', size=9)
    y -= 20

    for lbl, val in [
        ('Наименование:', P['company']),
        ('ИНН:', '309876543'),
        ('Рег. №:', 'GU-2024/09-00847'),
        ('Дата регистрации:', '01.09.2024'),
        ('Юр. адрес:', 'г. Ташкент, Юнусабадский р-н, ул. Амира Темура 45, оф. 301'),
        ('Уставной капитал:', '50,000,000 UZS'),
        ('Учредитель:', P['fio']),
        ('Доля:', '100%'),
        ('Директор:', P['fio']),
        ('Осн. ОКВЭД:', '62010 — Разработка программного обеспечения'),
        ('Доп. ОКВЭД:', '63110, 63120, 62020'),
    ]:
        c.setFont('FB', 10)
        c.drawString(50, y, lbl)
        c.setFont('F', 10)
        c.drawString(220, y, val)
        y -= 18

    stamp(c, 420, y+20, 'МИНЮСТ', 'РУз')


# =====================================================
# 11. Гарантийное письмо
# =====================================================
def guarantor_letter(c):
    y = 780
    line(c, 350, y, f'Ташкент, {TODAY}')
    y -= 25
    y = lines(c, 50, y, ['В Консульство Французской Республики', 'г. Ташкент'])
    y -= 10
    c.setFont('FB', 12)
    c.drawCentredString(W/2, y, 'ПИСЬМО ФИНАНСОВОГО ГАРАНТА')
    y -= 25
    y = lines(c, 50, y, [
        'Я, Халиулин Рустам Ибрагимович (паспорт AB9876543),',
        f'являюсь отцом заявителя — {P["fio"]}.',
        '',
        'Настоящим гарантирую финансовое обеспечение поездки',
        'моего сына во Францию в период 15.05.2026 — 29.05.2026.',
        '',
        'Я готов покрыть все расходы, связанные с поездкой:',
        '  — проживание и питание',
        '  — транспортные расходы',
        '  — медицинские расходы (при необходимости)',
        '',
        'Мои финансовые возможности подтверждаются:',
        '  — Справка из банка (остаток: $25,000)',
        '  — Справка о доходах (15,000,000 UZS/мес.)',
        '',
        'Контактные данные:',
        '  Тел: +998 90 123 45 67',
        '  Адрес: г. Ташкент, Чиланзарский р-н, ул. Бунёдкор 12',
        '',
        '',
        'Подпись: _______________',
        'Халиулин Рустам Ибрагимович',
    ])


# =====================================================
# 12. Справка о составе семьи
# =====================================================
def family_comp(c):
    y = hdr(c, 'СПРАВКА О СОСТАВЕ СЕМЬИ', 'Юнусабадский районный отдел ЗАГС г. Ташкента')
    line(c, 400, y+28, f'№ 0313-2026/ЗС', size=9)
    line(c, 400, y+16, f'от {TODAY}', size=9)

    y = lines(c, 50, y, [
        f'Выдана гражданину: {P["fio"]}',
        f'Адрес регистрации: {P["addr"]}',
        '',
        'По данным домовой книги, по указанному адресу зарегистрированы:',
        '',
    ])

    # Table
    c.setFont('FB', 10)
    for h, x in [('№', 50), ('ФИО', 80), ('Год рожд.', 350), ('Родство', 440)]:
        c.drawString(x, y, h)
    y -= 5
    separator(c, y)
    y -= 10

    c.setFont('F', 10)
    for num, fio, yr, rel in [
        ('1', P['fio'], '1990', 'Заявитель'),
        ('2', P['wife'], '1992', 'Супруга'),
        ('3', P['son'], '2018', 'Сын'),
        ('4', P['daughter'], '2021', 'Дочь'),
    ]:
        for val, x in [(num, 50), (fio, 80), (yr, 350), (rel, 440)]:
            c.drawString(x, y, val)
        y -= 16

    y -= 15
    y = lines(c, 50, y, [
        'Справка выдана для предъявления по месту требования.',
        '',
        'Начальник отдела ЗАГС          _______________ / Мирзаева Г.Т.',
    ])
    stamp(c, 200, y+5, 'ЗАГС', 'Юнусабад')


# =====================================================
# 13. Квитанция визового сбора
# =====================================================
def visa_fee(c):
    y = hdr(c, 'RECEIPT / КВИТАНЦИЯ', 'VFS Global — France Visa Application Centre, Tashkent')

    for lbl, val in [
        ('Receipt No:', 'VFS-TAS-2026-048712'),
        ('Date:', datetime.now().strftime('%d %b %Y')),
        ('', ''),
        ('Applicant:', f'{P["last"]} {P["first"]}'),
        ('Passport:', P['pnum']),
        ('Type:', 'Short-stay visa (C) — Tourism'),
        ('', ''),
        ('Visa Fee:', 'EUR 80.00'),
        ('Service Fee:', 'EUR 32.00'),
        ('SMS Notification:', 'EUR 2.00'),
        ('', '────────────────'),
        ('TOTAL:', 'EUR 114.00'),
        ('Equivalent:', '1,482,000 UZS (rate: 13,000)'),
        ('', ''),
        ('Payment:', 'Cash'),
        ('Status:', 'PAID'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(250, y, val)
        y -= 16

    stamp(c, 460, y+25, 'VFS', 'GLOBAL')


# =====================================================
# 14. Согласие на обработку данных
# =====================================================
def consent(c):
    y = hdr(c, 'СОГЛАСИЕ НА ОБРАБОТКУ ПЕРСОНАЛЬНЫХ ДАННЫХ')
    y = lines(c, 50, y, [
        f'Я, {P["fio"]},',
        f'дата рождения: {P["dob"]}, паспорт: {P["pnum"]},',
        '',
        'настоящим даю своё добровольное согласие на обработку моих',
        'персональных данных Генеральным Консульством Франции в г. Ташкенте',
        'и компанией VFS Global для целей рассмотрения визового заявления.',
        '',
        'Обрабатываемые данные: ФИО, дата и место рождения, данные паспорта,',
        'адрес, контактные данные, финансовые сведения, биометрические данные.',
        '',
        'Цель обработки: рассмотрение заявления на краткосрочную визу',
        'категории C (туристическая).',
        '',
        'Согласие действует до момента его отзыва в письменной форме.',
        '',
        '',
        f'Дата: {TODAY}',
        '',
        f'Подпись: _______________  / {P["fio"]}',
    ])


# =====================================================
# 15. Копии предыдущих виз
# =====================================================
def prev_visas(c):
    y = hdr(c, 'КОПИИ ПРЕДЫДУЩИХ ВИЗ', f'Паспорт: {P["pnum"]}')

    visas = [
        ('ИСПАНИЯ (Шенген)', [
            ('Тип:', 'C — краткосрочная (туризм)'),
            ('Номер:', 'ESP 4782156'),
            ('Выдана:', '01.07.2023'),
            ('Срок:', '01.07.2023 — 15.07.2023'),
            ('Въезды:', 'Однократная'),
            ('Дней:', '15'),
            ('Статус:', 'ИСПОЛЬЗОВАНА — возврат вовремя'),
        ]),
        ('ТУРЦИЯ', [
            ('Тип:', 'e-Visa (туризм)'),
            ('Номер:', 'TUR-2022-9847231'),
            ('Выдана:', '01.10.2022'),
            ('Срок:', '01.10.2022 — 01.01.2023'),
            ('Въезды:', 'Мультивиза'),
            ('Дней:', '12 из 90'),
            ('Статус:', 'ИСПОЛЬЗОВАНА — возврат вовремя'),
        ]),
        ('ОАЭ', [
            ('Тип:', 'Tourist Visa'),
            ('Номер:', 'UAE-2024-11847'),
            ('Выдана:', '01.03.2024'),
            ('Срок:', '01.03.2024 — 01.05.2024'),
            ('Дней:', '7 из 30'),
            ('Статус:', 'ИСПОЛЬЗОВАНА — возврат вовремя'),
        ]),
    ]
    for title, fields in visas:
        c.setFont('FB', 11)
        c.drawString(50, y, f'ВИЗА — {title}')
        y -= 18
        for lbl, val in fields:
            c.setFont('F', 9)
            c.drawString(70, y, lbl)
            c.drawString(170, y, val)
            y -= 14
        y -= 8


# =====================================================
# 16. Резюме / CV
# =====================================================
def cv(c):
    y = 780
    c.setFont('FB', 16)
    c.drawCentredString(W/2, y, P['fio_en'])
    y -= 16
    c.setFont('F', 10)
    c.drawCentredString(W/2, y, f'{P["phone"]} | {P["email"]} | Tashkent, Uzbekistan')
    y -= 10
    separator(c, y)
    y -= 10

    sections = [
        ('PROFESSIONAL SUMMARY', [
            f'CEO and Founder of VISABOR TECHNOLOGY. 10+ years in IT management,',
            'software development, and digital transformation. Expert in SaaS platforms,',
            'CRM systems, and visa/immigration technology solutions.',
        ]),
        ('EXPERIENCE', [
            'CEO / Founder — VISABOR TECHNOLOGY (Sep 2024 — Present)',
            '  Building SaaS CRM platform for visa agencies in Central Asia.',
            '  Managing team of 8 engineers. Revenue: $50K ARR.',
            '',
            'CTO — inBank.asia (2021 — 2024)',
            '  Led technology department. Built online banking platform.',
            '  Managed team of 25 developers. 500K+ active users.',
            '',
            'IT Director — Ministry of Labour (2017 — 2021)',
            '  Digital transformation of government services.',
            '  Launched 5 national digital platforms.',
        ]),
        ('EDUCATION', [
            'MBA — Westminster International University in Tashkent (2015)',
            'BSc Computer Science — National University of Uzbekistan (2012)',
        ]),
        ('SKILLS', [
            'Leadership, Product Management, PHP/Laravel, Vue.js, PostgreSQL,',
            'System Architecture, Team Management, Agile/Scrum',
        ]),
        ('LANGUAGES', [
            'Russian — Native | Uzbek — Fluent | English — Professional (B2)',
        ]),
    ]

    for title, lns in sections:
        c.setFont('FB', 11)
        c.drawString(50, y, title)
        y -= 5
        separator(c, y)
        y -= 8
        for l in lns:
            c.setFont('F', 9)
            c.drawString(60, y, l)
            y -= 13
        y -= 5


# =====================================================
# 17. DS-160 Confirmation Page (USA)
# =====================================================
def ds160_confirmation(c):
    y = hdr(c, 'U.S. DEPARTMENT OF STATE', 'Consular Electronic Application Center')
    c.setFont('FB', 14)
    c.drawCentredString(W/2, y, 'DS-160 CONFIRMATION PAGE')
    y -= 25
    c.setFont('F', 10)
    c.drawCentredString(W/2, y, 'Nonimmigrant Visa Application')
    y -= 25

    c.setFont('FB', 10)
    c.setFillColor(HexColor('#006600'))
    c.drawCentredString(W/2, y, 'YOUR APPLICATION HAS BEEN SUCCESSFULLY SUBMITTED')
    c.setFillColor(black)
    y -= 25

    for lbl, val in [
        ('Application ID:', 'AA00A1B2C3'),
        ('Confirmation Number:', '20260315TAS00847'),
        ('Barcode:', '||||| |||| ||||| ||||| |||| |||||'),
        ('', ''),
        ('Surname:', P['last']),
        ('Given Name:', P['first']),
        ('Date of Birth:', '15-MAR-1990'),
        ('Nationality:', 'UZBEKISTAN'),
        ('Passport Number:', P['pnum']),
        ('Gender:', 'MALE'),
        ('', ''),
        ('Visa Class:', 'B1/B2 — Business/Tourism'),
        ('Embassy/Consulate:', 'U.S. Embassy Tashkent, Uzbekistan'),
        ('Interview Date:', '20 May 2026, 09:00'),
        ('', ''),
        ('Application Created:', datetime.now().strftime('%d %b %Y')),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(250, y, val)
        y -= 16

    y -= 10
    c.setFont('F', 8)
    c.setFillColor(gray)
    y = lines(c, 50, y, [
        'IMPORTANT: Print this confirmation page. You must bring it to your visa interview.',
        'This page does not guarantee visa issuance. You must appear in person at the Embassy.',
        'Please arrive 15 minutes before your scheduled interview time.',
    ], size=8)
    c.setFillColor(black)


# =====================================================
# 18. MRV Fee Receipt (USA visa fee $185)
# =====================================================
def mrv_fee_receipt(c):
    y = hdr(c, 'MRV FEE RECEIPT', 'U.S. Visa Application — Machine Readable Visa Fee')
    c.setFont('F', 9)
    c.drawString(400, y+28, 'CGI Federal')
    y -= 5

    for lbl, val in [
        ('Receipt Number:', 'MRV-2026-TAS-0084712'),
        ('Transaction Date:', datetime.now().strftime('%d %b %Y')),
        ('', ''),
        ('Applicant Name:', f'{P["last"]}, {P["first"]}'),
        ('Date of Birth:', '15-MAR-1990'),
        ('Passport Number:', P['pnum']),
        ('Nationality:', 'UZBEKISTAN'),
        ('', ''),
        ('Visa Category:', 'B1/B2 (Business/Tourism)'),
        ('Fee Amount:', 'USD $185.00'),
        ('Payment Method:', 'Bank Transfer — Kapitalbank'),
        ('Payment Status:', 'PAID'),
        ('Bank Reference:', 'KB-2026-0315-48721'),
        ('', ''),
        ('Embassy:', 'U.S. Embassy Tashkent'),
        ('Validity:', 'This receipt is valid for 1 year from payment date'),
        ('', ''),
        ('CGI Reference:', 'CGI-TAS-2026-03-15-00847'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(250, y, val)
        y -= 16

    y -= 10
    c.setFont('F', 8)
    c.setFillColor(gray)
    y = lines(c, 50, y, [
        'This receipt confirms payment of the MRV (Machine Readable Visa) fee.',
        'Present this receipt at your visa interview. Fee is non-refundable.',
        'Payment processed by CGI Federal on behalf of U.S. Department of State.',
    ], size=8)
    c.setFillColor(black)


# =====================================================
# 19. Свидетельство о предпринимательстве
# =====================================================
def business_certificate(c):
    y = hdr(c, 'СВИДЕТЕЛЬСТВО О ГОСУДАРСТВЕННОЙ РЕГИСТРАЦИИ', 'индивидуального предпринимателя')
    line(c, 50, y, 'Министерство юстиции Республики Узбекистан', size=9)
    y -= 20

    c.setFont('FB', 10)
    c.drawCentredString(W/2, y, 'Серия ИП № 001-2024/ТШ-08471')
    y -= 25

    for lbl, val in [
        ('ФИО:', P['fio']),
        ('Дата рождения:', P['dob']),
        ('Паспорт:', P['pnum']),
        ('ИНН:', P['inn']),
        ('', ''),
        ('Вид деятельности:', 'Разработка и внедрение программного обеспечения'),
        ('ОКВЭД:', '62.01 — Разработка компьютерного ПО'),
        ('Доп. ОКВЭД:', '63.11 — Обработка данных, хостинг'),
        ('', ''),
        ('Адрес регистрации:', P['addr']),
        ('Дата регистрации:', '15.08.2024'),
        ('Орган регистрации:', 'Юнусабадское РГНИ г. Ташкента'),
        ('', ''),
        ('Режим налогообложения:', 'Упрощённая система (4%)'),
    ]:
        c.setFont('FB' if lbl else 'F', 10)
        c.drawString(50, y, lbl)
        c.setFont('F', 10)
        c.drawString(250, y, val)
        y -= 18

    y -= 10
    y = lines(c, 50, y, [
        'Свидетельство бессрочное.',
        '',
        'Начальник РГНИ             _______________ / Азимов С.Р.',
    ])
    stamp(c, 420, y+20, 'РГНИ', 'Юнусабад')


# =====================================================
# 20. I-20 Form (USA Student)
# =====================================================
def i20_form(c):
    y = hdr(c, 'CERTIFICATE OF ELIGIBILITY (I-20)', 'U.S. Department of Homeland Security — SEVP')
    c.setFont('F', 9)
    c.drawString(50, y, 'Form I-20, Certificate of Eligibility for Nonimmigrant Student Status')
    y -= 20

    for lbl, val in [
        ('SEVIS ID:', 'N0012345678'),
        ('School:', 'University of California, Berkeley'),
        ('School Code:', 'SFR214F00112000'),
        ('', ''),
        ('Student Name:', f'{P["last"]}, {P["first"]}'),
        ('Country of Birth:', 'UZBEKISTAN'),
        ('Country of Citizenship:', 'UZBEKISTAN'),
        ('Date of Birth:', '15 March 1990'),
        ('Admission Number:', 'I-94 pending'),
        ('', ''),
        ('Level of Education:', 'Master\'s (Graduate)'),
        ('Program:', 'M.S. in Computer Science'),
        ('Program Start Date:', '15 August 2026'),
        ('Program End Date:', '15 May 2028'),
        ('English Proficiency:', 'IELTS 7.0 — meets requirement'),
        ('', ''),
        ('Estimated Cost of Attendance (1 year):', ''),
        ('  Tuition & Fees:', '$44,066'),
        ('  Living Expenses:', '$22,878'),
        ('  Health Insurance:', '$3,286'),
        ('  TOTAL:', '$70,230'),
        ('', ''),
        ('Source of Funding:', ''),
        ('  Personal/Family:', '$50,000'),
        ('  University Scholarship:', '$20,230'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(300, y, val)
        y -= 15

    y -= 10
    c.setFont('F', 8)
    c.setFillColor(gray)
    c.drawString(50, y, 'DSO Signature: Dr. Sarah M. Collins | Date: ' + datetime.now().strftime('%d %b %Y'))
    c.setFillColor(black)


# =====================================================
# 21. SEVIS Fee Receipt (USA Student)
# =====================================================
def sevis_fee(c):
    y = hdr(c, 'SEVIS I-901 FEE RECEIPT', 'U.S. Immigration and Customs Enforcement')
    y -= 5

    for lbl, val in [
        ('Receipt Number:', 'I901-2026-0315-48721'),
        ('Payment Date:', datetime.now().strftime('%d %b %Y')),
        ('', ''),
        ('Family Name:', P['last']),
        ('Given Name:', P['first']),
        ('Date of Birth:', '15-MAR-1990'),
        ('Country of Birth:', 'UZBEKISTAN'),
        ('Country of Citizenship:', 'UZBEKISTAN'),
        ('', ''),
        ('SEVIS ID:', 'N0012345678'),
        ('School Name:', 'University of California, Berkeley'),
        ('Visa Type:', 'F-1 (Student)'),
        ('', ''),
        ('Fee Amount:', 'USD $350.00'),
        ('Payment Method:', 'Visa Credit Card ending ****4521'),
        ('Status:', 'PAID'),
        ('', ''),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(250, y, val)
        y -= 16

    c.setFont('F', 8)
    c.setFillColor(gray)
    y = lines(c, 50, y, [
        'This receipt confirms payment of the SEVIS I-901 fee.',
        'You must bring this receipt to your visa interview at the U.S. Embassy.',
        'Processed by FMJfee.com — U.S. Immigration and Customs Enforcement.',
    ], size=8)
    c.setFillColor(black)


# =====================================================
# 22. Academic Transcript (USA Student)
# =====================================================
def academic_transcript(c):
    y = hdr(c, 'OFFICIAL ACADEMIC TRANSCRIPT',
            'Westminster International University in Tashkent')
    c.setFont('F', 9)
    c.drawString(50, y, f'Student: {P["fio_en"]}')
    c.drawString(350, y, 'Student ID: WIUT-2013-04521')
    y -= 16
    c.drawString(50, y, 'Program: MBA (Master of Business Administration)')
    c.drawString(350, y, 'Graduated: June 2015')
    y -= 25

    c.setFont('FB', 9)
    for h, x in [('Code', 50), ('Course', 120), ('Credits', 380), ('Grade', 440), ('GPA', 490)]:
        c.drawString(x, y, h)
    y -= 5
    separator(c, y)
    y -= 10

    courses = [
        ('MBA501', 'Strategic Management', '4', 'A', '4.0'),
        ('MBA502', 'Financial Accounting', '4', 'A-', '3.7'),
        ('MBA503', 'Marketing Management', '3', 'B+', '3.3'),
        ('MBA504', 'Organizational Behavior', '3', 'A', '4.0'),
        ('MBA505', 'Operations Management', '3', 'A-', '3.7'),
        ('MBA506', 'Business Law', '3', 'B+', '3.3'),
        ('MBA507', 'Research Methods', '3', 'A', '4.0'),
        ('MBA508', 'International Business', '4', 'A', '4.0'),
        ('MBA509', 'Business Analytics', '3', 'A-', '3.7'),
        ('MBA510', 'Entrepreneurship', '3', 'A', '4.0'),
        ('MBA599', 'MBA Thesis', '6', 'A', '4.0'),
    ]
    c.setFont('F', 9)
    for code, name, cr, gr, gpa in courses:
        for val, x in [(code, 50), (name, 120), (cr, 390), (gr, 440), (gpa, 495)]:
            c.drawString(x, y, val)
        y -= 14

    y -= 5
    separator(c, y)
    y -= 10
    c.setFont('FB', 10)
    c.drawString(50, y, 'Cumulative GPA: 3.79 / 4.00')
    c.drawString(300, y, 'Total Credits: 39')
    y -= 20
    c.setFont('F', 9)
    y = lines(c, 50, y, [
        'Degree Conferred: Master of Business Administration',
        'Date of Graduation: 20 June 2015',
        'Honors: Cum Laude',
        '',
        'Registrar: _______________ / Dr. James Robertson',
        f'Date Issued: {TODAY}',
    ], size=9)
    stamp(c, 440, y+30, 'WIUT', 'Registrar')


# =====================================================
# 23. Diploma (USA Student)
# =====================================================
def diploma(c):
    y = 700
    c.setFont('FB', 16)
    c.drawCentredString(W/2, y, 'WESTMINSTER INTERNATIONAL')
    y -= 20
    c.setFont('FB', 16)
    c.drawCentredString(W/2, y, 'UNIVERSITY IN TASHKENT')
    y -= 30
    c.setFont('F', 11)
    c.drawCentredString(W/2, y, 'Affiliated with the University of Westminster, London')
    y -= 40

    c.setFont('F', 12)
    c.drawCentredString(W/2, y, 'This is to certify that')
    y -= 25
    c.setFont('FB', 18)
    c.drawCentredString(W/2, y, P['fio_en'])
    y -= 25
    c.setFont('F', 12)
    c.drawCentredString(W/2, y, 'has successfully completed the requirements for the degree of')
    y -= 25
    c.setFont('FB', 16)
    c.drawCentredString(W/2, y, 'MASTER OF BUSINESS ADMINISTRATION')
    y -= 20
    c.setFont('F', 11)
    c.drawCentredString(W/2, y, 'Cum Laude')
    y -= 30
    c.drawCentredString(W/2, y, 'Conferred on the 20th day of June, 2015')
    y -= 60

    c.setFont('F', 10)
    c.drawString(80, y, '___________________')
    c.drawString(350, y, '___________________')
    y -= 15
    c.drawString(80, y, 'Rector')
    c.drawString(350, y, 'Registrar')
    y -= 12
    c.setFont('F', 9)
    c.drawString(80, y, 'Prof. Bakhtiyor Islamov')
    c.drawString(350, y, 'Dr. James Robertson')

    stamp(c, W/2, y-20, 'WIUT', 'DIPLOMA')


# =====================================================
# 24. Language Certificate (USA Student)
# =====================================================
def language_cert(c):
    y = hdr(c, 'IELTS TEST REPORT FORM', 'British Council — International English Language Testing System')
    c.setFont('F', 9)
    c.drawString(400, y+28, 'TRF Number: 26UZ000847', size=9) if False else None
    line(c, 400, y+28, 'TRF Number: 26UZ000847', size=9)
    y -= 5

    for lbl, val in [
        ('Candidate Name:', f'{P["first"]} {P["last"]}'),
        ('Date of Birth:', '15/03/1990'),
        ('Candidate Number:', 'UZ000847'),
        ('Nationality:', 'Uzbekistan'),
        ('First Language:', 'Russian'),
        ('', ''),
        ('Test Date:', '15 January 2026'),
        ('Test Centre:', 'British Council Tashkent (UZ001)'),
        ('Test Format:', 'Academic'),
        ('', ''),
        ('RESULTS:', ''),
        ('  Listening:', '7.5'),
        ('  Reading:', '7.0'),
        ('  Writing:', '6.5'),
        ('  Speaking:', '7.0'),
        ('', ''),
        ('  OVERALL BAND SCORE:', '7.0'),
        ('', ''),
        ('CEFR Level:', 'C1 — Effective Operational Proficiency'),
        ('Valid Until:', '15 January 2028'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(250, y, val)
        y -= 16

    stamp(c, 460, y+30, 'IELTS', 'British Council')


# =====================================================
# 25. Study Plan (USA Student)
# =====================================================
def study_plan(c):
    y = 780
    line(c, 350, y, f'Tashkent, {TODAY}')
    y -= 25
    c.setFont('FB', 12)
    c.drawCentredString(W/2, y, 'STATEMENT OF PURPOSE / STUDY PLAN')
    y -= 25

    y = lines(c, 50, y, [
        f'Applicant: {P["fio_en"]}',
        f'Program: M.S. in Computer Science, UC Berkeley',
        f'Term: Fall 2026 — Spring 2028',
        '',
        'Dear Admissions Committee,',
        '',
        'I am writing to express my strong interest in the Master of Science',
        'in Computer Science program at the University of California, Berkeley.',
        '',
        'ACADEMIC BACKGROUND:',
        'I hold an MBA from Westminster International University in Tashkent',
        '(GPA 3.79/4.0), and a BSc in Computer Science from the National',
        'University of Uzbekistan. My academic foundation spans both business',
        'strategy and technical computing.',
        '',
        'PROFESSIONAL EXPERIENCE:',
        'As CEO of VISABOR TECHNOLOGY, I have built a SaaS CRM platform',
        'serving visa agencies across Central Asia. Previously, as CTO of',
        'inBank.asia, I led a team of 25 developers managing 500K+ users.',
        '',
        'RESEARCH INTERESTS:',
        'I am particularly interested in:',
        '  — Machine Learning for document verification and fraud detection',
        '  — Natural Language Processing for multilingual systems',
        '  — Distributed systems architecture for fintech applications',
        '',
        'POST-GRADUATION PLANS:',
        'After completing my degree, I plan to return to Uzbekistan to expand',
        'VISABOR TECHNOLOGY with AI-powered features and contribute to the',
        'growing tech ecosystem in Central Asia.',
        '',
        'I am confident that UC Berkeley\'s program will provide me with',
        'the skills and network to achieve these goals.',
        '',
        'Sincerely,',
        P['fio_en'],
    ])


# =====================================================
# 26. Conference Invitation (USA Business)
# =====================================================
def conference_invitation(c):
    y = hdr(c, 'INVITATION LETTER', 'TechCrunch Disrupt 2026 — San Francisco')
    c.setFont('F', 9)
    c.drawString(400, y+28, datetime.now().strftime('%d %b %Y'))
    y -= 5

    y = lines(c, 50, y, [
        'TO: U.S. Embassy Tashkent, Uzbekistan',
        'RE: Visa Support Letter for Conference Attendee',
        '',
        'Dear Visa Officer,',
        '',
        'We are pleased to confirm that the following individual has been',
        'registered and approved to attend TechCrunch Disrupt 2026:',
        '',
        f'  Name: {P["fio_en"]}',
        f'  Passport: {P["pnum"]}',
        f'  Company: VISABOR TECHNOLOGY (CEO)',
        f'  Country: Uzbekistan',
        '',
        'Event Details:',
        '  Event: TechCrunch Disrupt 2026',
        '  Dates: June 15-17, 2026',
        '  Venue: Moscone Center, San Francisco, CA 94103',
        '  Registration ID: TC-2026-STARTUP-08471',
        '  Badge Type: Startup Exhibitor',
        '',
        'Mr. Khaliulin has been selected to present his company in the',
        'Startup Battlefield competition. His attendance is essential.',
        '',
        'All conference-related expenses (registration, booth) have been',
        'covered by TechCrunch. Travel and accommodation will be covered',
        'by the attendee\'s company.',
        '',
        'We kindly request that you facilitate the visa process for this',
        'important participant.',
        '',
        'Sincerely,',
        '',
        'Rebecca Johnson',
        'Head of Events, TechCrunch',
        'events@techcrunch.com | +1 (415) 555-0199',
    ])
    stamp(c, 460, 200, 'TC', 'DISRUPT')


# =====================================================
# 27. Company Cover Letter for Business Trip (USA)
# =====================================================
def business_cover_letter(c):
    y = 780
    line(c, 350, y, f'Tashkent, {TODAY}')
    y -= 25
    y = lines(c, 50, y, [
        'U.S. Embassy',
        'Consular Section',
        'Tashkent, Uzbekistan',
    ])
    y -= 10
    c.setFont('FB', 12)
    c.drawCentredString(W/2, y, 'COMPANY COVER LETTER')
    c.setFont('F', 10)
    y -= 20
    c.drawCentredString(W/2, y, 'Request for B1 Business Visa')
    y -= 25

    y = lines(c, 50, y, [
        'Dear Visa Officer,',
        '',
        f'I, {P["fio"]}, CEO of {P["company"]},',
        'respectfully request a B1 business visa to visit the United States',
        'for the following business purposes:',
        '',
        '1. Attend TechCrunch Disrupt 2026 (June 15-17, San Francisco)',
        '2. Meet with potential investors and partners in Silicon Valley',
        '3. Visit Amazon Web Services HQ for partnership discussions',
        '',
        'Trip Details:',
        '  Dates: June 12-22, 2026 (10 days)',
        '  Cities: San Francisco, CA → Seattle, WA',
        '  Accommodation: Hotel (prepaid, see booking confirmation)',
        '',
        'About VISABOR TECHNOLOGY:',
        '  — SaaS CRM platform for visa agencies',
        '  — 50+ paying customers in Central Asia',
        '  — 8 employees in Tashkent office',
        '  — Annual revenue: $50,000 USD',
        '',
        f'I have strong ties to Uzbekistan: family ({P["wife"]} and two children),',
        'property, and an active business. I will return after the trip.',
        '',
        'All expenses will be covered by the company.',
        '',
        'Sincerely,',
        P['fio'],
        f'{P["position"]}, {P["company"]}',
        f'Tel: {P["phone"]}',
        f'Email: {P["email"]}',
    ])
    stamp(c, 460, 200, 'VISABOR', 'TECH')


# =====================================================
# 28. Business Agenda (USA Business)
# =====================================================
def business_agenda(c):
    y = hdr(c, 'BUSINESS TRIP AGENDA', 'United States — June 12-22, 2026')
    c.setFont('F', 10)
    c.drawString(50, y, f'Traveler: {P["fio_en"]}, CEO of VISABOR TECHNOLOGY')
    y -= 25

    days = [
        ('Jun 12', 'TAS → SFO', 'Travel day. Uzbekistan Airways via Istanbul.'),
        ('Jun 13', 'San Francisco', 'Arrival. Check-in Marriott Union Square. Rest.'),
        ('Jun 14', 'San Francisco', 'Investor meeting: Sequoia Capital (10:00-12:00).'),
        ('', '', 'Investor meeting: Y Combinator (14:00-16:00).'),
        ('Jun 15', 'San Francisco', 'TechCrunch Disrupt — Day 1. Startup Battlefield setup.'),
        ('Jun 16', 'San Francisco', 'TechCrunch Disrupt — Day 2. Startup Battlefield pitch.'),
        ('Jun 17', 'San Francisco', 'TechCrunch Disrupt — Day 3. Networking, follow-ups.'),
        ('Jun 18', 'San Francisco', 'Meeting: Stripe (partnership). Free evening.'),
        ('Jun 19', 'SFO → SEA', 'Flight to Seattle. Check-in Hyatt Regency.'),
        ('Jun 20', 'Seattle', 'AWS HQ visit — cloud partnership for CIS market.'),
        ('Jun 21', 'Seattle', 'Meeting: Microsoft for Startups program.'),
        ('Jun 22', 'SEA → TAS', 'Return flight via Istanbul. Arrival Jun 23.'),
    ]
    for dt, loc, desc in days:
        c.setFont('FB', 9)
        c.drawString(50, y, dt)
        c.setFont('F', 9)
        c.drawString(110, y, loc)
        c.drawString(220, y, desc)
        y -= 14

    y -= 15
    y = lines(c, 50, y, [
        'Accommodation:',
        '  Jun 12-19: Marriott Union Square, San Francisco ($189/night)',
        '  Jun 19-22: Hyatt Regency, Seattle ($165/night)',
        '',
        'Estimated Budget: $4,850 USD (covered by VISABOR TECHNOLOGY)',
    ], size=9)


# =====================================================
# 29. Commercial Relationship Proof (USA Business)
# =====================================================
def commercial_proof(c):
    y = hdr(c, 'LETTER OF COMMERCIAL RELATIONSHIP')
    line(c, 400, y+28, datetime.now().strftime('%d %b %Y'), size=9)
    y -= 5

    y = lines(c, 50, y, [
        'Amazon Web Services, Inc.',
        '410 Terry Avenue North',
        'Seattle, WA 98109, USA',
        '',
        '',
        'To Whom It May Concern,',
        '',
        f'This letter confirms that VISABOR TECHNOLOGY (Tashkent, Uzbekistan),',
        f'represented by CEO {P["fio_en"]}, is an active customer and',
        'technology partner of Amazon Web Services.',
        '',
        'Partnership Details:',
        f'  — AWS Account ID: 847123456789',
        f'  — Active Since: October 2024',
        f'  — Service Tier: Business Support',
        f'  — Monthly Spend: ~$800 USD',
        f'  — Services Used: EC2, RDS, S3, CloudFront, SES',
        '',
        'VISABOR TECHNOLOGY has been invited to visit AWS headquarters',
        'in Seattle for a partner consultation regarding AWS Activate',
        'program expansion for Central Asian markets.',
        '',
        'We confirm that Mr. Khaliulin\'s visit is necessary for business',
        'discussions that cannot be conducted remotely.',
        '',
        'Sincerely,',
        '',
        'Michael R. Thompson',
        'Partner Solutions Architect — Emerging Markets',
        'Amazon Web Services',
        'michael.thompson@amazon.com',
    ])
    stamp(c, 460, 220, 'AWS', 'Partner')


# =====================================================
# 30. USA Hotel Booking
# =====================================================
def usa_hotel_booking(c):
    y = hdr(c, 'HOTEL RESERVATION CONFIRMATION', 'Hotels.com — Confirmation')
    line(c, 400, y+28, f'Date: {datetime.now().strftime("%d %b %Y")}', size=9)

    c.setFont('FB', 11)
    y = line(c, 50, y, 'Hotel: Marriott Union Square ****', 'FB', 11)
    y = line(c, 50, y, 'Address: 480 Sutter Street, San Francisco, CA 94108, USA')
    y -= 5

    for lbl, val in [
        ('Guest Name:', f'{P["first"]} {P["last"]}'),
        ('Confirmation:', 'HT-2026-SFO-08471'),
        ('Check-in:', '12 June 2026, 15:00'),
        ('Check-out:', '19 June 2026, 11:00'),
        ('Duration:', '7 nights'),
        ('Room Type:', 'King Room, City View'),
        ('Rate:', 'USD 189.00 per night'),
        ('Total:', 'USD 1,323.00 (prepaid)'),
        ('Status:', 'CONFIRMED'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(200, y, val)
        y -= 16

    y -= 10
    y = separator(c, y)
    c.setFont('FB', 11)
    y = line(c, 50, y, 'Hotel: Hyatt Regency Seattle ***', 'FB', 11)
    y = line(c, 50, y, 'Address: 808 Howell Street, Seattle, WA 98101, USA')
    y -= 5

    for lbl, val in [
        ('Guest Name:', f'{P["first"]} {P["last"]}'),
        ('Confirmation:', 'HT-2026-SEA-08472'),
        ('Check-in:', '19 June 2026, 15:00'),
        ('Check-out:', '22 June 2026, 11:00'),
        ('Duration:', '3 nights'),
        ('Total:', 'USD 495.00 (prepaid)'),
        ('Status:', 'CONFIRMED'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(200, y, val)
        y -= 16


# =====================================================
# 31. USA Air Tickets
# =====================================================
def usa_air_tickets(c):
    y = hdr(c, 'ELECTRONIC TICKET / ITINERARY RECEIPT', 'Turkish Airlines — e-Ticket')
    c.setFont('F', 10)
    c.drawString(50, y, 'Booking Reference: TK8M4N')
    c.drawString(350, y, f'Date: {datetime.now().strftime("%d %b %Y")}')
    y -= 16
    c.drawString(50, y, f'Passenger: {P["last"]}/{P["first"]} MR')
    c.drawString(350, y, 'Ticket: 235-7891234567')
    y -= 25

    c.setFont('FB', 11)
    c.drawCentredString(W/2, y, '--- OUTBOUND ---')
    y -= 20
    for flt, dt, route, dep, arr, dur in [
        ('TK 372', '12 JUN 2026', 'TAS — IST', 'Tashkent 06:15', 'Istanbul 09:45', '4h 30m'),
        ('TK 079', '12 JUN 2026', 'IST — SFO', 'Istanbul 13:30', 'San Francisco 17:45', '13h 15m'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, f'Flight: {flt}')
        c.drawString(200, y, dt)
        c.drawString(370, y, route)
        y -= 15
        c.setFont('F', 9)
        c.drawString(70, y, f'Departure: {dep}  →  Arrival: {arr}  ({dur})')
        y -= 22

    y -= 5
    c.setFont('FB', 11)
    c.drawCentredString(W/2, y, '--- INTERNAL ---')
    y -= 20
    c.setFont('F', 10)
    c.drawString(50, y, 'Flight: AS 328')
    c.drawString(200, y, '19 JUN 2026')
    c.drawString(370, y, 'SFO — SEA')
    y -= 15
    c.setFont('F', 9)
    c.drawString(70, y, 'Departure: San Francisco 10:00  →  Arrival: Seattle 12:15  (2h 15m)')
    y -= 25

    c.setFont('FB', 11)
    c.drawCentredString(W/2, y, '--- RETURN ---')
    y -= 20
    for flt, dt, route, dep, arr, dur in [
        ('TK 204', '22 JUN 2026', 'SEA — IST', 'Seattle 17:30', 'Istanbul 14:30+1', '11h 00m'),
        ('TK 371', '23 JUN 2026', 'IST — TAS', 'Istanbul 19:15', 'Tashkent 02:45+1', '4h 30m'),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, f'Flight: {flt}')
        c.drawString(200, y, dt)
        c.drawString(370, y, route)
        y -= 15
        c.setFont('F', 9)
        c.drawString(70, y, f'Departure: {dep}  →  Arrival: {arr}  ({dur})')
        y -= 22

    y -= 5
    c.setFont('F', 10)
    c.drawString(50, y, 'Class: Economy (Y) | Baggage: 2 x 23 kg')
    y -= 15
    c.drawString(50, y, 'Total Fare: USD 1,245.00 | Status: CONFIRMED / TICKETED')


# =====================================================
# 32. USA Travel Insurance
# =====================================================
def usa_travel_insurance(c):
    y = hdr(c, 'TRAVEL INSURANCE POLICY', 'INGO Uzbekistan — Coverage for USA')
    line(c, 400, y+28, 'Policy #: TM-2026-US-08471', size=9)

    y -= 5
    for lbl, val in [
        ('Insured:', P['fio']),
        ('Date of Birth:', P['dob']),
        ('Passport:', P['pnum']),
        ('', ''),
        ('Territory:', 'United States of America'),
        ('Period:', '12.06.2026 — 24.06.2026 (12 days)'),
        ('Coverage:', 'USD 50,000 (medical expenses)'),
        ('', 'USD 10,000 (emergency evacuation)'),
        ('', 'USD 5,000 (lost baggage)'),
        ('', 'USD 3,000 (trip cancellation)'),
        ('', ''),
        ('Emergency Phone:', '+998 71 120 60 60 (24/7)'),
        ('US Assistance:', '+1 (800) 555-0147'),
        ('Assistance Company:', 'World Assist International'),
        ('', ''),
        ('Premium:', 'USD 45.00'),
        ('Status:', 'ACTIVE'),
        ('Issued:', TODAY),
    ]:
        c.setFont('F', 10)
        c.drawString(50, y, lbl)
        c.drawString(220, y, val)
        y -= 16

    stamp(c, 460, y+30, 'INGO', 'INSURANCE')


# =====================================================
# GENERATE ALL
# =====================================================
print('Генерация тестовых документов...\n')

# Copy real docs with Russian names
SRC = '/Users/pmkhali/Library/CloudStorage/GoogleDrive-pkhaliulin@gmail.com/Мой диск/Личные документы /Документы виза Испания'
import shutil

real_docs = [
    ('000_Загран_PulatKhaliulin.pdf', f'Загранпаспорт{SUFFIX}.pdf'),
    ('001_ID_Pulat Khaliulin.pdf', f'Внутренний паспорт{SUFFIX}.pdf'),
    ('010_свидетельство о браке_PulatKhaliulin.pdf', f'Свидетельство о браке{SUFFIX}.pdf'),
    ('011_UZ_метрика Артура_PulatKhaliulin.pdf', f'Метрика ребёнка Артур{SUFFIX}.pdf'),
    ('012_метрики Сабрина_PulatKhaliulin.pdf', f'Метрика ребёнка Сабрина{SUFFIX}.pdf'),
    ('014_Спарвка_ЗП_PulatKhaliulin.PDF', f'Справка о доходах{SUFFIX}.pdf'),
    ('015_Выписка_доходы_mygovuz_PulatKhaliulin.PDF', f'Выписка по карте{SUFFIX}.pdf'),
    ('008_Справка_о_трудоустройстве_PulatKhaliulin.PDF', f'Справка с работы{SUFFIX}.pdf'),
    ('006_UZ_Трудовая_mygovuz_PulatKhaliulin.pdf', f'Трудовая книжка{SUFFIX}.pdf'),
    ('007_Приказ о командировании_PulatKhaliulin.PDF', f'Командировочный приказ{SUFFIX}.pdf'),
    ('009_Приказ_отпуск_PulatKhaliulin.PDF', f'Разрешение на отпуск{SUFFIX}.pdf'),
    ('003_Nota Simple RM SPITCH IBERIA junio 2023_PulatKhaliulin.pdf', f'Документы на недвижимость{SUFFIX}.pdf'),
    ('004_Visa InvLetter__Spanish-fin_firma_PulatKhaliulin.pdf', f'Приглашение от компании{SUFFIX}.pdf'),
    ('002_Mygovuz_Прописка_PulatKhaliulin.pdf', f'Выписка о недвижимости{SUFFIX}.pdf'),
]

for src_name, dst_name in real_docs:
    src_path = os.path.join(SRC, src_name)
    dst_path = os.path.join(OUT, dst_name)
    if os.path.exists(src_path):
        shutil.copy2(src_path, dst_path)
        print(f'  ✓ {dst_name}')
    else:
        print(f'  ✗ NOT FOUND: {src_name}')

print()

# Generate PDFs
pdf(f'Справка об остатке на счёте{SUFFIX}.pdf', bank_balance)
pdf(f'Сопроводительное письмо{SUFFIX}.pdf', cover_letter)
pdf(f'Бронирование отеля{SUFFIX}.pdf', hotel_booking)
pdf(f'Авиабилеты{SUFFIX}.pdf', air_tickets)
pdf(f'Медицинская страховка{SUFFIX}.pdf', travel_insurance)
pdf(f'Маршрутный лист{SUFFIX}.pdf', itinerary)
pdf(f'Налоговая декларация{SUFFIX}.pdf', tax_return)
pdf(f'Справка о несудимости{SUFFIX}.pdf', criminal_record)
pdf(f'Доказательства привязки к стране{SUFFIX}.pdf', proof_of_ties)
pdf(f'Регистрация компании{SUFFIX}.pdf', company_reg)
pdf(f'Письмо финансового гаранта{SUFFIX}.pdf', guarantor_letter)
pdf(f'Справка о составе семьи{SUFFIX}.pdf', family_comp)
pdf(f'Квитанция визового сбора{SUFFIX}.pdf', visa_fee)
pdf(f'Согласие на обработку данных{SUFFIX}.pdf', consent)
pdf(f'Копии предыдущих виз{SUFFIX}.pdf', prev_visas)
pdf(f'Резюме CV{SUFFIX}.pdf', cv)

# USA Documents
print('\nДокументы для визы в США:')
pdf(f'DS-160 подтверждение{SUFFIX}.pdf', ds160_confirmation)
pdf(f'Квитанция MRV визовый сбор США{SUFFIX}.pdf', mrv_fee_receipt)
pdf(f'Свидетельство о предпринимательстве{SUFFIX}.pdf', business_certificate)
pdf(f'Форма I-20 (студент){SUFFIX}.pdf', i20_form)
pdf(f'Квитанция SEVIS (студент){SUFFIX}.pdf', sevis_fee)
pdf(f'Академическая справка{SUFFIX}.pdf', academic_transcript)
pdf(f'Диплом{SUFFIX}.pdf', diploma)
pdf(f'Сертификат IELTS{SUFFIX}.pdf', language_cert)
pdf(f'Учебный план (Study Plan){SUFFIX}.pdf', study_plan)
pdf(f'Приглашение на конференцию{SUFFIX}.pdf', conference_invitation)
pdf(f'Сопроводительное письмо бизнес США{SUFFIX}.pdf', business_cover_letter)
pdf(f'Деловая программа США{SUFFIX}.pdf', business_agenda)
pdf(f'Подтверждение коммерческих связей{SUFFIX}.pdf', commercial_proof)
pdf(f'Бронирование отеля США{SUFFIX}.pdf', usa_hotel_booking)
pdf(f'Авиабилеты США{SUFFIX}.pdf', usa_air_tickets)
pdf(f'Медицинская страховка США{SUFFIX}.pdf', usa_travel_insurance)

# Count
total = len([f for f in os.listdir(OUT) if f.endswith('.pdf')])
print(f'\nГотово! Всего: {total} документов')
