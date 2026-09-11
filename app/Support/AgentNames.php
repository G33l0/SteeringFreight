<?php

namespace App\Support;

/**
 * The first name a customer sees when somebody joins their chat.
 *
 * Representatives answer customers all over the world, and a shift name keeps
 * the conversation human without putting a real member of staff's name, and the
 * hours they work, in front of strangers on the public internet. The name is
 * chosen once per conversation and stored on it, so the customer keeps talking
 * to the same person for as long as the thread lives.
 *
 * The panel always shows the real account behind every reply, so the audit
 * trail and the assignment queue are unaffected.
 */
class AgentNames
{
    /**
     * Deliberately ordinary, widely used first names from several regions, with
     * no surnames: a first name alone reads as a person on a support desk
     * rather than as a claim about a specific individual.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'Adam', 'Adaeze', 'Aisha', 'Alex', 'Amara', 'Amir', 'Ana', 'Andre',
            'Bella', 'Bruno', 'Carlos', 'Chen', 'Chloe', 'Clara', 'Daniel', 'Dele',
            'Diana', 'Eli', 'Elena', 'Emeka', 'Emma', 'Farah', 'Felix', 'Grace',
            'Hana', 'Hassan', 'Ibrahim', 'Isabel', 'Ivan', 'Jonas', 'Julia', 'Kemi',
            'Lars', 'Leila', 'Liam', 'Lucia', 'Marco', 'Maya', 'Mei', 'Nadia',
            'Noah', 'Omar', 'Priya', 'Rafael', 'Rosa', 'Samir', 'Sofia', 'Tunde',
            'Yara', 'Zainab',
        ];
    }

    /**
     * A name for a new conversation, avoiding any that are already in use on
     * conversations still open, so two people are not talking to the same
     * "Grace" in the same window.
     *
     * @param  list<string>  $inUse
     */
    public static function pick(array $inUse = []): string
    {
        $available = array_values(array_diff(self::all(), $inUse));

        // Every name taken at once only happens on a very busy desk; reusing
        // one is better than failing to answer the customer.
        $pool = $available !== [] ? $available : self::all();

        return $pool[random_int(0, count($pool) - 1)];
    }
}
