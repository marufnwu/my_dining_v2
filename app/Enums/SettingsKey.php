<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum SettingsKey : string
{
    use EnumToArray;
    case APP_NAME = 'app_name';
    case APP_URL = 'app_url';
    case APP_LOGO = 'app_logo';
    case APP_FAVICON = 'app_favicon';
    case APP_DESCRIPTION = 'app_description';
    case APP_KEYWORDS = 'app_keywords';
    case APP_EMAIL = 'app_email';
    case APP_PHONE = 'app_phone';
    case APP_ADDRESS = 'app_address';
    case APP_COUNTRY = 'app_country';
    case APP_CITY = 'app_city';
    case APP_ZIP = 'app_zip';
    case APP_TIMEZONE = 'app_timezone';
    case APP_DATE_FORMAT = 'app_date_format';
    case APP_TIME_FORMAT = 'app_time_format';
    case APP_CURRENCY = 'app_currency';
    case APP_CURRENCY_SYMBOL = 'app_currency_symbol';
    case APP_CURRENCY_POSITION = 'app_currency_position';
    case APP_CURRENCY_DECIMAL = 'app_currency_decimal';
    case APP_CURRENCY_THOUSANDS = 'app_currency_thousands';
    case APP_CURRENCY_DECIMAL_PLACES = 'app_currency_decimal_places';
    case APP_CURRENCY_DECIMAL_SEPARATOR = 'app_currency_decimal_separator';
    case APP_CURRENCY_THOUSANDS_SEPARATOR = 'app_currency_thousands_separator';
    case APP_CURRENCY_CODE = 'app_currency_code';
    case APP_CURRENCY_EXCHANGE_RATE = 'app_currency_exchange_rate';
    case ENABLE_EMAIL_VERIFICATION = 'enable_email_verification';

}
