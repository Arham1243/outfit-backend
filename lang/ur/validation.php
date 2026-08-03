<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute فیلڈ کو قبول کیا جانا ضروری ہے۔',
    'accepted_if' => ':attribute فیلڈ کو قبول کیا جانا ضروری ہے جب :other کی قدر :value ہو۔',
    'active_url' => ':attribute فیلڈ ایک درست URL ہونا ضروری ہے۔',
    'after' => ':attribute فیلڈ :date کے بعد کی تاریخ ہونی چاہیے۔',
    'after_or_equal' => ':attribute فیلڈ :date کے برابر یا بعد کی تاریخ ہونی چاہیے۔',
    'alpha' => ':attribute فیلڈ میں صرف حروف ہونے چاہئیں۔',
    'alpha_dash' => ':attribute فیلڈ میں صرف حروف، اعداد، ڈیش، اور انڈر اسکور ہونے چاہئیں۔',
    'alpha_num' => ':attribute فیلڈ میں صرف حروف اور اعداد ہونے چاہئیں۔',
    'array' => ':attribute فیلڈ ایک array ہونا ضروری ہے۔',
    'ascii' => ':attribute فیلڈ میں صرف سنگل بائٹ حروفِ ابجد اور علامات ہونی چاہئیں۔',
    'before' => ':attribute فیلڈ :date سے پہلے کی تاریخ ہونی چاہیے۔',
    'before_or_equal' => ':attribute فیلڈ :date کے برابر یا پہلے کی تاریخ ہونی چاہیے۔',
    'between' => [
        'array' => ':attribute فیلڈ میں :min سے :max آئٹمز کے درمیان ہونا ضروری ہے۔',
        'file' => ':attribute فیلڈ :min سے :max کلوبائٹس کے درمیان ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ :min اور :max کے درمیان ہونی چاہیے۔',
        'string' => ':attribute فیلڈ :min سے :max حروف کے درمیان ہونی چاہیے۔',
    ],
    'boolean' => ':attribute فیلڈ true یا false ہونی چاہیے۔',
    'can' => ':attribute فیلڈ میں ایک غیر مجاز قدر موجود ہے۔',
    'confirmed' => ':attribute فیلڈ کی تصدیق مطابقت نہیں رکھتی۔',
    'contains' => ':attribute فیلڈ میں ایک ضروری قدر موجود نہیں ہے۔',
    'current_password' => 'پاس ورڈ غلط ہے۔',
    'date' => ':attribute فیلڈ ایک درست تاریخ ہونی چاہیے۔',
    'date_equals' => ':attribute فیلڈ :date کے برابر تاریخ ہونی چاہیے۔',
    'date_format' => ':attribute فیلڈ کا فارمیٹ :format سے مطابقت رکھنا ضروری ہے۔',
    'decimal' => ':attribute فیلڈ میں :decimal اعشاریہ مقامات ہونے چاہئیں۔',
    'declined' => ':attribute فیلڈ کو مسترد کیا جانا ضروری ہے۔',
    'declined_if' => ':attribute فیلڈ کو مسترد کیا جانا ضروری ہے جب :other کی قدر :value ہو۔',
    'different' => ':attribute فیلڈ اور :other مختلف ہونے چاہئیں۔',
    'digits' => ':attribute فیلڈ :digits ہندسوں پر مشتمل ہونی چاہیے۔',
    'digits_between' => ':attribute فیلڈ :min اور :max ہندسوں کے درمیان ہونی چاہیے۔',
    'dimensions' => ':attribute فیلڈ کی تصویری جہتیں غلط ہیں۔',
    'distinct' => ':attribute فیلڈ میں ایک مکرر قدر موجود ہے۔',
    'doesnt_end_with' => ':attribute فیلڈ درج ذیل میں سے کسی پر ختم نہیں ہونی چاہیے: :values۔',
    'doesnt_start_with' => ':attribute فیلڈ درج ذیل میں سے کسی سے شروع نہیں ہونی چاہیے: :values۔',
    'email' => ':attribute فیلڈ ایک درست ای میل پتہ ہونا ضروری ہے۔',
    'ends_with' => ':attribute فیلڈ درج ذیل میں سے کسی ایک پر ختم ہونی چاہیے: :values۔',
    'enum' => 'منتخب کردہ :attribute غلط ہے۔',
    'exists' => 'منتخب کردہ :attribute غلط ہے۔',
    'extensions' => ':attribute فیلڈ میں درج ذیل میں سے ایک توسیع ہونی چاہیے: :values۔',
    'file' => ':attribute فیلڈ ایک فائل ہونی چاہیے۔',
    'filled' => ':attribute فیلڈ میں ایک قدر ہونی چاہیے۔',
    'gt' => [
        'array' => ':attribute فیلڈ میں :value سے زیادہ آئٹمز ہونے چاہئیں۔',
        'file' => ':attribute فیلڈ :value کلوبائٹس سے زیادہ ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ :value سے زیادہ ہونی چاہیے۔',
        'string' => ':attribute فیلڈ :value حروف سے زیادہ ہونی چاہیے۔',
    ],
    'gte' => [
        'array' => ':attribute فیلڈ میں :value یا زیادہ آئٹمز ہونے چاہئیں۔',
        'file' => ':attribute فیلڈ :value کلوبائٹس یا اس سے زیادہ ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ :value یا اس سے زیادہ ہونی چاہیے۔',
        'string' => ':attribute فیلڈ :value حروف یا اس سے زیادہ ہونی چاہیے۔',
    ],
    'hex_color' => ':attribute فیلڈ ایک درست ہیکساڈیسیمل رنگ ہونا چاہیے۔',
    'image' => ':attribute فیلڈ ایک تصویر ہونی چاہیے۔',
    'in' => 'منتخب کردہ :attribute غلط ہے۔',
    'in_array' => ':attribute فیلڈ :other میں موجود ہونی چاہیے۔',
    'integer' => ':attribute فیلڈ ایک صحیح عدد ہونا ضروری ہے۔',
    'ip' => ':attribute فیلڈ ایک درست IP پتہ ہونا ضروری ہے۔',
    'ipv4' => ':attribute فیلڈ ایک درست IPv4 پتہ ہونا ضروری ہے۔',
    'ipv6' => ':attribute فیلڈ ایک درست IPv6 پتہ ہونا ضروری ہے۔',
    'json' => ':attribute فیلڈ ایک درست JSON سٹرنگ ہونی چاہیے۔',
    'list' => ':attribute فیلڈ ایک فہرست ہونی چاہیے۔',
    'lowercase' => ':attribute فیلڈ چھوٹے حروف میں ہونی چاہیے۔',
    'lt' => [
        'array' => ':attribute فیلڈ میں :value سے کم آئٹمز ہونے چاہئیں۔',
        'file' => ':attribute فیلڈ :value کلوبائٹس سے کم ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ :value سے کم ہونی چاہیے۔',
        'string' => ':attribute فیلڈ :value حروف سے کم ہونی چاہیے۔',
    ],
    'lte' => [
        'array' => ':attribute فیلڈ میں :value سے زیادہ آئٹمز نہیں ہونے چاہئیں۔',
        'file' => ':attribute فیلڈ :value کلوبائٹس یا اس سے کم ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ :value یا اس سے کم ہونی چاہیے۔',
        'string' => ':attribute فیلڈ :value حروف یا اس سے کم ہونی چاہیے۔',
    ],
    'mac_address' => ':attribute فیلڈ ایک درست MAC پتہ ہونا ضروری ہے۔',
    'max' => [
        'array' => ':attribute فیلڈ میں :max سے زیادہ آئٹمز نہیں ہونے چاہئیں۔',
        'file' => ':attribute فیلڈ :max کلوبائٹس سے زیادہ نہیں ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ :max سے زیادہ نہیں ہونی چاہیے۔',
        'string' => ':attribute فیلڈ :max حروف سے زیادہ نہیں ہونی چاہیے۔',
    ],
    'max_digits' => ':attribute فیلڈ میں :max سے زیادہ ہندسے نہیں ہونے چاہئیں۔',
    'mimes' => ':attribute فیلڈ اس قسم کی فائل ہونی چاہیے: :values۔',
    'mimetypes' => ':attribute فیلڈ اس قسم کی فائل ہونی چاہیے: :values۔',
    'min' => [
        'array' => ':attribute فیلڈ میں کم از کم :min آئٹمز ہونے چاہئیں۔',
        'file' => ':attribute فیلڈ کم از کم :min کلوبائٹس ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ کم از کم :min ہونی چاہیے۔',
        'string' => ':attribute فیلڈ کم از کم :min حروف ہونی چاہیے۔',
    ],
    'min_digits' => ':attribute فیلڈ میں کم از کم :min ہندسے ہونے چاہئیں۔',
    'missing' => ':attribute فیلڈ غیر موجود ہونی چاہیے۔',
    'missing_if' => ':attribute فیلڈ غیر موجود ہونی چاہیے جب :other کی قدر :value ہو۔',
    'missing_unless' => ':attribute فیلڈ غیر موجود ہونی چاہیے جب تک کہ :other کی قدر :value نہ ہو۔',
    'missing_with' => ':attribute فیلڈ غیر موجود ہونی چاہیے جب :values موجود ہو۔',
    'missing_with_all' => ':attribute فیلڈ غیر موجود ہونی چاہیے جب :values موجود ہوں۔',
    'multiple_of' => ':attribute فیلڈ :value کا ضرب ہونی چاہیے۔',
    'not_in' => 'منتخب کردہ :attribute غلط ہے۔',
    'not_regex' => ':attribute فیلڈ کا فارمیٹ غلط ہے۔',
    'numeric' => ':attribute فیلڈ ایک عدد ہونا ضروری ہے۔',
    'password' => [
        'letters' => ':attribute فیلڈ میں کم از کم ایک حرف ہونا ضروری ہے۔',
        'mixed' => ':attribute فیلڈ میں کم از کم ایک بڑا اور ایک چھوٹا حرف ہونا ضروری ہے۔',
        'numbers' => ':attribute فیلڈ میں کم از کم ایک عدد ہونا ضروری ہے۔',
        'symbols' => ':attribute فیلڈ میں کم از کم ایک علامت ہونا ضروری ہے۔',
        'uncompromised' => 'یہ :attribute ڈیٹا لیک میں ظاہر ہو چکا ہے۔ براہ کرم کوئی مختلف :attribute منتخب کریں۔',
    ],
    'present' => ':attribute فیلڈ موجود ہونی چاہیے۔',
    'present_if' => ':attribute فیلڈ موجود ہونی چاہیے جب :other کی قدر :value ہو۔',
    'present_unless' => ':attribute فیلڈ موجود ہونی چاہیے جب تک کہ :other کی قدر :value نہ ہو۔',
    'present_with' => ':attribute فیلڈ موجود ہونی چاہیے جب :values موجود ہو۔',
    'present_with_all' => ':attribute فیلڈ موجود ہونی چاہیے جب :values موجود ہوں۔',
    'prohibited' => ':attribute فیلڈ ممنوع ہے۔',
    'prohibited_if' => ':attribute فیلڈ ممنوع ہے جب :other کی قدر :value ہو۔',
    'prohibited_if_accepted' => ':attribute فیلڈ ممنوع ہے جب :other قبول کیا گیا ہو۔',
    'prohibited_if_declined' => ':attribute فیلڈ ممنوع ہے جب :other مسترد کیا گیا ہو۔',
    'prohibited_unless' => ':attribute فیلڈ ممنوع ہے جب تک کہ :other کی قدر :values میں نہ ہو۔',
    'prohibits' => ':attribute فیلڈ :other کی موجودگی کی اجازت نہیں دیتی۔',
    'regex' => ':attribute فیلڈ کا فارمیٹ غلط ہے۔',
    'required' => ':attribute فیلڈ لازمی ہے۔',
    'required_array_keys' => ':attribute فیلڈ میں :values کے لیے اندراجات ہونے ضروری ہیں۔',
    'required_if' => ':attribute فیلڈ لازمی ہے جب :other کی قدر :value ہو۔',
    'required_if_accepted' => ':attribute فیلڈ لازمی ہے جب :other قبول کیا گیا ہو۔',
    'required_if_declined' => ':attribute فیلڈ لازمی ہے جب :other مسترد کیا گیا ہو۔',
    'required_unless' => ':attribute فیلڈ لازمی ہے جب تک کہ :other کی قدر :values میں نہ ہو۔',
    'required_with' => ':attribute فیلڈ لازمی ہے جب :values موجود ہو۔',
    'required_with_all' => ':attribute فیلڈ لازمی ہے جب :values موجود ہوں۔',
    'required_without' => ':attribute فیلڈ لازمی ہے جب :values موجود نہ ہو۔',
    'required_without_all' => ':attribute فیلڈ لازمی ہے جب :values میں سے کوئی بھی موجود نہ ہو۔',
    'same' => ':attribute فیلڈ :other سے مطابقت رکھنی چاہیے۔',
    'size' => [
        'array' => ':attribute فیلڈ میں :size آئٹمز ہونے چاہئیں۔',
        'file' => ':attribute فیلڈ :size کلوبائٹس ہونی چاہیے۔',
        'numeric' => ':attribute فیلڈ :size ہونی چاہیے۔',
        'string' => ':attribute فیلڈ :size حروف ہونی چاہیے۔',
    ],
    'starts_with' => ':attribute فیلڈ درج ذیل میں سے کسی ایک سے شروع ہونی چاہیے: :values۔',
    'string' => ':attribute فیلڈ ایک سٹرنگ ہونی چاہیے۔',
    'timezone' => ':attribute فیلڈ ایک درست ٹائم زون ہونا ضروری ہے۔',
    'unique' => ':attribute پہلے سے استعمال میں ہے۔',
    'uploaded' => ':attribute اپ لوڈ ہونے میں ناکام رہا۔',
    'uppercase' => ':attribute فیلڈ بڑے حروف میں ہونی چاہیے۔',
    'url' => ':attribute فیلڈ ایک درست URL ہونا ضروری ہے۔',
    'ulid' => ':attribute فیلڈ ایک درست ULID ہونا ضروری ہے۔',
    'uuid' => ':attribute فیلڈ ایک درست UUID ہونا ضروری ہے۔',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Field labels live in attributes.php and are merged here so the validator
    | can resolve validation.attributes.* while keeping Laravel rule lines above.
    |
    */

    'attributes' => require __DIR__.'/attributes.php',

];
