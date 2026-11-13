<?php
/**
 * Korisničke uloge – moraju odgovarati ENUM vrijednostima
 * kolone KORISNICI.uloga u našoj bazi podataka.
 */
class Roles
{
    const ADMIN = 'admin';
    const MAJSTOR = 'majstor';

    public static function all()
    {
        return [self::ADMIN, self::MAJSTOR];
    }
}
