<?php

if (! function_exists('email_template_is_date_key')) {
    function email_template_is_date_key(string $key): bool
    {
        return in_array($key, ['date', 'created_at', 'updated_at', 'paid_at', 'returned_at', 'approved_at'], true)
            || str_ends_with($key, '_date');
    }
}

if (! function_exists('format_email_template_replacements')) {
    /**
     * @param  array<string, mixed>  $replacements
     * @return array<string, mixed>
     */
    function format_email_template_replacements(array $replacements, $company = null): array
    {
        foreach ($replacements as $key => $value) {
            if (! is_string($key) || is_array($value) || $value === null || $value === '') {
                continue;
            }

            if (email_template_is_date_key($key)) {
                $replacements[$key] = format_date($value);
            }
        }

        return $replacements;
    }
}

if (! function_exists('render_template')) {
    function render_template(?string $template, array $replacements = [], $company = null): string
    {
        if ($template === null) {
            return '';
        }

        $replacements = format_email_template_replacements($replacements);

        foreach ($replacements as $key => $value) {
            if (is_array($value)) {
                $value = '<ul><li>'.implode('</li><li>', array_map('htmlspecialchars', $value)).'</li></ul>';
            } else {
                $value = htmlspecialchars($value);
            }

            $template = str_replace('{{ '.$key.' }}', $value, $template);
        }

        return nl2br($template);
    }
}

if (! function_exists('php_date_format_for_company_date_setting')) {
    function php_date_format_for_company_date_setting(?string $dateFormat): string
    {
        return match ($dateFormat) {
            'mm/dd/yy' => 'm/d/Y',
            'dd/mm/yy' => 'd/m/Y',
            'yy-mm-dd' => 'Y-m-d',
            default => 'M d, Y',
        };
    }
}

if (! function_exists('format_generated_on_for_company')) {
    function format_generated_on_for_company($company = null): string
    {
        try {
            $dt = \Carbon\Carbon::now((string) config('app.timezone'));
        } catch (\Throwable) {
            $dt = now();
        }

        return $dt->format(php_date_format_for_company_date_setting(null)).' '.$dt->format('h:i A');
    }
}

if (! function_exists('format_date')) {
    function format_date($date, $format = null, $company = null)
    {
        if ($date === null || $date === '') {
            return '';
        }

        $format = $format ?: get_company_date_format();
        $displayTz = (string) config('app.timezone');

        try {
            if ($date instanceof \Carbon\CarbonInterface) {
                $dt = $date->copy()->setTimezone($displayTz);
            } elseif ($date instanceof \DateTimeInterface) {
                $dt = \Carbon\Carbon::parse($date)->setTimezone($displayTz);
            } elseif (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($date))) {
                $dt = \Carbon\Carbon::createFromFormat('!Y-m-d', trim($date), $displayTz)->startOfDay();
            } else {
                $dt = \Carbon\Carbon::parse($date, $displayTz)->setTimezone($displayTz);
            }

            return $dt->format($format);
        } catch (\Throwable) {
            return is_scalar($date) ? (string) $date : '';
        }
    }
}

if (! function_exists('get_company_date_format')) {
    function get_company_date_format(): string
    {
        return php_date_format_for_company_date_setting(null);
    }
}

if (! function_exists('pdf_export_hours_columns')) {
    /**
     * @return array<int, string>
     */
    function pdf_export_hours_columns(): array
    {
        return ['hours', 'input_hours', 'billable_hours'];
    }
}

if (! function_exists('pdf_export_currency_columns')) {
    /**
     * @return array<int, string>
     */
    function pdf_export_currency_columns(): array
    {
        return [
            'amount', 'display_amount', 'total_amount', 'billable_amount',
            'non_billable_amount', 'billable_expenses', 'additional_items_total',
            'total_billable', 'outstanding_balance', 'outstanding', 'credit_card_amount',
            'charge_amount', 'cost_rate', 'charge_rate', 'billing_rate',
            'allowed_customer_credit', 'amount_received', 'total_cost', 'total_billing',
            'margin_abs', 'payment_applied', 'difference_amount',
        ];
    }
}

if (! function_exists('pdf_export_cell_is_right_aligned')) {
    function pdf_export_cell_is_right_aligned(string $key): bool
    {
        return in_array($key, pdf_export_hours_columns(), true)
            || in_array($key, pdf_export_currency_columns(), true)
            || $key === 'margin_pct';
    }
}
