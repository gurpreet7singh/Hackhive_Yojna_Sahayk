
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

class ChatController
{
    private PDO $db;

    public function __construct()
    {
        global $pdo;

        $this->db = $pdo;
    }


    /* =========================================
       CREATE NEW CHAT SESSION
    ========================================= */

    public function createSession(?int $userId, string $language = 'en')
    {
        try {

            $stmt = $this->db->prepare("
                INSERT INTO chat_sessions (user_id, session_title, language)
                VALUES (:user_id, 'New Conversation', :language)
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':language' => $language
            ]);

            $sessionId = $this->db->lastInsertId();

            return [
                'success' => true,
                'session_id' => (int) $sessionId
            ];

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to create chat session.'
            ];
        }
    }


    /* =========================================
       SAVE CHAT MESSAGE
       ========================================= */

    public function saveMessage(
        int $sessionId,
        string $sender,
        string $message,
        string $status = 'complete'
    ) {

        try {

            $stmt = $this->db->prepare("
                INSERT INTO chat_messages
                    (session_id, sender, message_text, response_status)
                VALUES
                    (:session_id, :sender, :message_text, :response_status)
            ");

            $stmt->execute([
                ':session_id' => $sessionId,
                ':sender' => $sender,
                ':message_text' => $message,
                ':response_status' => $status
            ]);

            return [
                'success' => true,
                'message_id' => (int) $this->db->lastInsertId()
            ];

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to save message.'
            ];
        }
    }


    /* =========================================
       GET USER CHAT SESSIONS
       ========================================= */

    public function getSessions(int $userId)
    {
        try {

            $stmt = $this->db->prepare("
                SELECT
                    cs.id,
                    cs.session_title,
                    cs.language,
                    cs.created_at,
                    cs.updated_at,

                    (
                        SELECT message_text
                        FROM chat_messages
                        WHERE session_id = cs.id
                        ORDER BY created_at DESC, id DESC
                        LIMIT 1
                    ) AS last_message

                FROM chat_sessions cs

                WHERE cs.user_id = :user_id

                ORDER BY cs.updated_at DESC
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);

            return [
                'success' => true,
                'sessions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to retrieve chat sessions.'
            ];
        }
    }


    /* =========================================
       GET MESSAGES FROM A SESSION
       ========================================= */

    public function getMessages(int $sessionId, int $userId)
    {
        try {

            /* Verify session belongs to user */

            $check = $this->db->prepare("
                SELECT id
                FROM chat_sessions
                WHERE id = :session_id
                AND user_id = :user_id
            ");

            $check->execute([
                ':session_id' => $sessionId,
                ':user_id' => $userId
            ]);

            if (!$check->fetch()) {

                return [
                    'success' => false,
                    'message' => 'Chat session not found.'
                ];
            }


            /* Retrieve messages */

            $stmt = $this->db->prepare("
                SELECT
                    id,
                    sender,
                    message_text,
                    response_status,
                    created_at

                FROM chat_messages

                WHERE session_id = :session_id

                ORDER BY created_at ASC, id ASC
            ");

            $stmt->execute([
                ':session_id' => $sessionId
            ]);

            return [
                'success' => true,
                'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to retrieve messages.'
            ];
        }
    }


    /* =========================================
       UPDATE SESSION TITLE
       ========================================= */

    public function updateSessionTitle(
        int $sessionId,
        int $userId,
        string $title
    ) {

        try {

            $stmt = $this->db->prepare("
                UPDATE chat_sessions

                SET session_title = :title

                WHERE id = :session_id
                AND user_id = :user_id
            ");

            $stmt->execute([
                ':title' => mb_substr($title, 0, 255),
                ':session_id' => $sessionId,
                ':user_id' => $userId
            ]);

            return [
                'success' => $stmt->rowCount() > 0
            ];

        } catch (PDOException $e) {

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to update session title.'
            ];
        }
    }


    /* =========================================
       DELETE CHAT SESSION
       ========================================= */

    public function deleteSession(int $sessionId, int $userId)
    {
        try {

            /* Verify ownership */

            $check = $this->db->prepare("
                SELECT id

                FROM chat_sessions

                WHERE id = :session_id
                AND user_id = :user_id
            ");

            $check->execute([
                ':session_id' => $sessionId,
                ':user_id' => $userId
            ]);

            if (!$check->fetch()) {

                return [
                    'success' => false,
                    'message' => 'Chat session not found.'
                ];
            }


            /* Delete messages first */

            $this->db->beginTransaction();

            $deleteMessages = $this->db->prepare("
                DELETE FROM chat_messages
                WHERE session_id = :session_id
            ");

            $deleteMessages->execute([
                ':session_id' => $sessionId
            ]);


            /* Delete session */

            $deleteSession = $this->db->prepare("
                DELETE FROM chat_sessions

                WHERE id = :session_id
                AND user_id = :user_id
            ");

            $deleteSession->execute([
                ':session_id' => $sessionId,
                ':user_id' => $userId
            ]);

            $this->db->commit();


            return [
                'success' => true,
                'message' => 'Chat session deleted successfully.'
            ];

        } catch (PDOException $e) {

            if ($this->db->inTransaction()) {

                $this->db->rollBack();

            }

            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to delete chat session.'
            ];
        }
    }

}
