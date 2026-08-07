<?php

namespace App\Support;

class FaceMode
{
    public const USER_FACE = 'user_face';

    public const AI_MODEL = 'ai_model';

    public const USER_BODY_AI_FACE = 'user_body_ai_face';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::USER_FACE,
            self::AI_MODEL,
            self::USER_BODY_AI_FACE,
        ];
    }

    public static function requiresFaceImage(?string $mode): bool
    {
        return in_array($mode, [self::USER_FACE, self::USER_BODY_AI_FACE], true);
    }
}
