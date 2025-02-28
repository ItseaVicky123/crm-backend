<?php

namespace App\Lib\Orders\Notes;

use App\Models\OrderHistoryNote;
use App\Models\OrderNoteTemplate;

/**
 * Class OrderHistoryNoteHandler
 *
 * @package App\Lib\Orders\Notes
 */
class OrderHistoryNoteHandler
{

    /**
     * Create a history note on the given order
     *
     * @param int $orderId
     * @param int $noteTemplateId
     * @param string $message
     * @return bool
     */
    public static function createNote(int $orderId, int $noteTemplateId, string $message): bool
    {
        // Load NoteTemplate
        $template = OrderNoteTemplate::where('id', $noteTemplateId)->first();

        // Create note
        OrderHistoryNote::create([
            'order_id'  => $orderId,
            'message'   => $message,
            'type_name' => $template->getHistoryTypeString(),
            'author'    => current_user()
        ]);

        return true;

    }

}
