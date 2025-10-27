<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

// Bot Configuration
define('BOT_TOKEN', '*******TOKEN*******');
define('ADMIN_ID', 123456789);
define('DATA_FILE', __DIR__ . '/bot_data.json');
define('USERS_FILE', __DIR__ . '/users_data.json');
define('TEMP_FILE', __DIR__ . '/temp_data.json');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Helper Functions
function loadData() {
    if (!file_exists(DATA_FILE)) {
        $defaultData = [
            'buttons' => [],
            'settings' => [
                'button_layout' => []
            ]
        ];
        saveData($defaultData);
        return $defaultData;
    }
    return json_decode(file_get_contents(DATA_FILE), true);
}

function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function loadUsers() {
    if (!file_exists(USERS_FILE)) {
        $defaultUsers = ['users' => [], 'stats' => ['total' => 0, 'active' => 0]];
        saveUsers($defaultUsers);
        return $defaultUsers;
    }
    return json_decode(file_get_contents(USERS_FILE), true);
}

function saveUsers($users) {
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function loadTemp() {
    if (!file_exists(TEMP_FILE)) {
        return [];
    }
    return json_decode(file_get_contents(TEMP_FILE), true);
}

function saveTemp($temp) {
    file_put_contents(TEMP_FILE, json_encode($temp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function registerUser($userId, $firstName, $username = null) {
    $users = loadUsers();
    
    $isNewUser = !isset($users['users'][$userId]);
    
    if ($isNewUser) {
        $users['users'][$userId] = [
            'id' => $userId,
            'first_name' => $firstName,
            'username' => $username,
            'joined_at' => date('Y-m-d H:i:s'),
            'last_active' => date('Y-m-d H:i:s'),
            'message_count' => 0,
            'last_message_ids' => []
        ];
        $users['stats']['total']++;
        saveUsers($users);
        
        // Send notification to admin
        $usernameText = $username ? "@{$username}" : "غير متوفر";
        $notification = "🎉 *عضو جديد دخل البوت!*\n\n";
        $notification .= "👤 الاسم: *{$firstName}*\n";
        $notification .= "🔗 المعرف: {$usernameText}\n";
        $notification .= "📊 إجمالي الأعضاء: *{$users['stats']['total']}*";
        
        sendMessage(ADMIN_ID, $notification);
    } else {
        $users['users'][$userId]['last_active'] = date('Y-m-d H:i:s');
        $users['users'][$userId]['first_name'] = $firstName;
        $users['users'][$userId]['username'] = $username;
        $users['users'][$userId]['message_count']++;
        saveUsers($users);
    }
}

function setUserLastMessages($userId, $messageIds) {
    $users = loadUsers();
    if (isset($users['users'][$userId])) {
        if (is_array($messageIds)) {
            $users['users'][$userId]['last_message_ids'] = $messageIds;
        } else {
            $users['users'][$userId]['last_message_ids'] = [$messageIds];
        }
        saveUsers($users);
    }
}

function getUserLastMessages($userId) {
    $users = loadUsers();
    return $users['users'][$userId]['last_message_ids'] ?? [];
}

function isAdmin($userId) {
    return $userId == ADMIN_ID;
}

function apiRequest($method, $parameters = []) {
    $url = API_URL . $method;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

function deleteMessage($chatId, $messageId) {
    return apiRequest('deleteMessage', [
        'chat_id' => $chatId,
        'message_id' => $messageId
    ]);
}

function sendMessage($chatId, $text, $replyMarkup = null, $parseMode = 'Markdown', $entities = null) {
    $params = [
        'chat_id' => $chatId,
        'text' => $text
    ];
    
    if ($parseMode) {
        $params['parse_mode'] = $parseMode;
    }
    
    if ($entities) {
        $params['entities'] = $entities;
        unset($params['parse_mode']);
    }
    
    if ($replyMarkup) {
        $params['reply_markup'] = $replyMarkup;
    }
    
    return apiRequest('sendMessage', $params);
}

function sendPhoto($chatId, $photo, $caption = '', $replyMarkup = null, $parseMode = 'Markdown', $captionEntities = null) {
    $params = [
        'chat_id' => $chatId,
        'photo' => $photo,
        'caption' => $caption
    ];
    
    if ($parseMode && !$captionEntities) {
        $params['parse_mode'] = $parseMode;
    }
    
    if ($captionEntities) {
        $params['caption_entities'] = $captionEntities;
    }
    
    if ($replyMarkup) {
        $params['reply_markup'] = $replyMarkup;
    }
    
    return apiRequest('sendPhoto', $params);
}

function sendVideo($chatId, $video, $caption = '', $replyMarkup = null, $parseMode = 'Markdown', $captionEntities = null) {
    $params = [
        'chat_id' => $chatId,
        'video' => $video,
        'caption' => $caption
    ];
    
    if ($parseMode && !$captionEntities) {
        $params['parse_mode'] = $parseMode;
    }
    
    if ($captionEntities) {
        $params['caption_entities'] = $captionEntities;
    }
    
    if ($replyMarkup) {
        $params['reply_markup'] = $replyMarkup;
    }
    
    return apiRequest('sendVideo', $params);
}

function sendDocument($chatId, $document, $caption = '', $replyMarkup = null, $parseMode = 'Markdown', $captionEntities = null) {
    $params = [
        'chat_id' => $chatId,
        'document' => $document,
        'caption' => $caption
    ];
    
    if ($parseMode && !$captionEntities) {
        $params['parse_mode'] = $parseMode;
    }
    
    if ($captionEntities) {
        $params['caption_entities'] = $captionEntities;
    }
    
    if ($replyMarkup) {
        $params['reply_markup'] = $replyMarkup;
    }
    
    return apiRequest('sendDocument', $params);
}

function sendMediaGroup($chatId, $media) {
    return apiRequest('sendMediaGroup', [
        'chat_id' => $chatId,
        'media' => $media
    ]);
}

function answerCallbackQuery($callbackQueryId, $text = '', $showAlert = false) {
    return apiRequest('answerCallbackQuery', [
        'callback_query_id' => $callbackQueryId,
        'text' => $text,
        'show_alert' => $showAlert
    ]);
}

function editMessageText($chatId, $messageId, $text, $replyMarkup = null) {
    $params = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    
    if ($replyMarkup) {
        $params['reply_markup'] = $replyMarkup;
    }
    
    return apiRequest('editMessageText', $params);
}

function getAdminPanel() {
    return [
        'inline_keyboard' => [
            [
                ['text' => '➕ إضافة زر', 'callback_data' => 'admin_add'],
                ['text' => '📋 قائمة الأزرار', 'callback_data' => 'admin_list']
            ],
            [
                ['text' => '📊 الإحصائيات', 'callback_data' => 'admin_stats'],
                ['text' => '🔄 ترتيب الأزرار', 'callback_data' => 'admin_layout']
            ],
            [
                ['text' => '📱 معاينة', 'callback_data' => 'admin_preview']
            ]
        ]
    ];
}

function buildUserKeyboard($data) {
    $layout = $data['settings']['button_layout'];
    $buttons = $data['buttons'];
    
    if (empty($buttons)) {
        return null;
    }
    
    $keyboard = [];
    
    if (empty($layout)) {
        foreach ($buttons as $button) {
            $keyboard[] = [
                ['text' => $button['name'], 'callback_data' => 'btn_' . $button['id'] . '_0']
            ];
        }
    } else {
        foreach ($layout as $row) {
            $rowButtons = [];
            foreach ($row as $index) {
                if (isset($buttons[$index])) {
                    $rowButtons[] = [
                        'text' => $buttons[$index]['name'],
                        'callback_data' => 'btn_' . $buttons[$index]['id'] . '_0'
                    ];
                }
            }
            if (!empty($rowButtons)) {
                $keyboard[] = $rowButtons;
            }
        }
    }
    
    return ['inline_keyboard' => $keyboard];
}

function getLayoutManager($data) {
    $buttons = $data['buttons'];
    $layout = $data['settings']['button_layout'];
    
    if (empty($buttons)) {
        return [
            'text' => '❌ لا توجد أزرار لترتيبها',
            'keyboard' => [
                'inline_keyboard' => [
                    [['text' => '🔙 رجوع', 'callback_data' => 'admin_back']]
                ]
            ]
        ];
    }
    
    $response = "🔄 *ترتيب الأزرار*\n\n";
    $response .= "*الترتيب الحالي:*\n\n";
    
    if (empty($layout)) {
        $response .= "افتراضي (زر واحد في كل صف)\n\n";
        $layout = [];
        foreach ($buttons as $index => $button) {
            $layout[] = [$index];
        }
    }
    
    foreach ($layout as $rowIndex => $row) {
        $rowNames = [];
        foreach ($row as $btnIndex) {
            if (isset($buttons[$btnIndex])) {
                $rowNames[] = $buttons[$btnIndex]['name'];
            }
        }
        $response .= "صف " . ($rowIndex + 1) . ": " . implode(' - ', $rowNames) . "\n";
    }
    
    $response .= "\n*اضغط على اسم الزر للتحكم فيه:*";
    
    $keyboard = [];
    foreach ($buttons as $index => $button) {
        $keyboard[] = [
            ['text' => ($index + 1) . '. ' . $button['name'], 'callback_data' => 'layout_btn_' . $index]
        ];
    }
    
    $keyboard[] = [
        ['text' => '🗑 مسح الترتيب', 'callback_data' => 'layout_clear'],
        ['text' => '💾 حفظ', 'callback_data' => 'layout_save']
    ];
    $keyboard[] = [['text' => '🔙 رجوع', 'callback_data' => 'admin_back']];
    
    return [
        'text' => $response,
        'keyboard' => ['inline_keyboard' => $keyboard]
    ];
}

function getButtonArrowControls($buttonIndex, $data) {
    $buttons = $data['buttons'];
    $layout = $data['settings']['button_layout'];
    
    if (empty($layout)) {
        $layout = [];
        foreach ($buttons as $index => $button) {
            $layout[] = [$index];
        }
    }
    
    $button = $buttons[$buttonIndex];
    
    $currentRow = -1;
    $currentCol = -1;
    
    foreach ($layout as $rowIdx => $row) {
        $colIdx = array_search($buttonIndex, $row);
        if ($colIdx !== false) {
            $currentRow = $rowIdx;
            $currentCol = $colIdx;
            break;
        }
    }
    
    $response = "🎯 *التحكم في الزر:* {$button['name']}\n\n";
    $response .= "استخدم الأسهم للتحريك:";
    
    $keyboard = [];
    
    if ($currentRow > 0) {
        $keyboard[] = [['text' => '⬆️ للأعلى', 'callback_data' => "move_{$buttonIndex}_up"]];
    }
    
    $leftRight = [];
    if ($currentCol > 0) {
        $leftRight[] = ['text' => '⬅️ لليسار', 'callback_data' => "move_{$buttonIndex}_left"];
    }
    if ($currentCol < count($layout[$currentRow]) - 1) {
        $leftRight[] = ['text' => '➡️ لليمين', 'callback_data' => "move_{$buttonIndex}_right"];
    }
    if (!empty($leftRight)) {
        $keyboard[] = $leftRight;
    }
    
    if ($currentRow < count($layout) - 1) {
        $keyboard[] = [['text' => '⬇️ للأسفل', 'callback_data' => "move_{$buttonIndex}_down"]];
    }
    
    $keyboard[] = [
        ['text' => '📤 صف جديد فوق', 'callback_data' => "move_{$buttonIndex}_newrow_above"],
        ['text' => '📥 صف جديد تحت', 'callback_data' => "move_{$buttonIndex}_newrow_below"]
    ];
    
    $keyboard[] = [['text' => '🔙 رجوع', 'callback_data' => 'admin_layout']];
    
    return [
        'text' => $response,
        'keyboard' => ['inline_keyboard' => $keyboard]
    ];
}

function moveButton($buttonIndex, $direction, &$data) {
    $layout = $data['settings']['button_layout'];
    
    if (empty($layout)) {
        $layout = [];
        foreach ($data['buttons'] as $index => $button) {
            $layout[] = [$index];
        }
    }
    
    $currentRow = -1;
    $currentCol = -1;
    
    foreach ($layout as $rowIdx => $row) {
        $colIdx = array_search($buttonIndex, $row);
        if ($colIdx !== false) {
            $currentRow = $rowIdx;
            $currentCol = $colIdx;
            break;
        }
    }
    
    if ($currentRow === -1) return false;
    
    switch ($direction) {
        case 'up':
            if ($currentRow > 0) {
                unset($layout[$currentRow][$currentCol]);
                $layout[$currentRow] = array_values($layout[$currentRow]);
                $layout[$currentRow - 1][] = $buttonIndex;
                if (empty($layout[$currentRow])) {
                    unset($layout[$currentRow]);
                    $layout = array_values($layout);
                }
            }
            break;
            
        case 'down':
            if ($currentRow < count($layout) - 1) {
                unset($layout[$currentRow][$currentCol]);
                $layout[$currentRow] = array_values($layout[$currentRow]);
                $layout[$currentRow + 1][] = $buttonIndex;
                if (empty($layout[$currentRow])) {
                    unset($layout[$currentRow]);
                    $layout = array_values($layout);
                }
            }
            break;
            
        case 'left':
            if ($currentCol > 0) {
                $temp = $layout[$currentRow][$currentCol - 1];
                $layout[$currentRow][$currentCol - 1] = $buttonIndex;
                $layout[$currentRow][$currentCol] = $temp;
            }
            break;
            
        case 'right':
            if ($currentCol < count($layout[$currentRow]) - 1) {
                $temp = $layout[$currentRow][$currentCol + 1];
                $layout[$currentRow][$currentCol + 1] = $buttonIndex;
                $layout[$currentRow][$currentCol] = $temp;
            }
            break;
            
        case 'newrow_above':
            unset($layout[$currentRow][$currentCol]);
            $layout[$currentRow] = array_values($layout[$currentRow]);
            array_splice($layout, $currentRow, 0, [[$buttonIndex]]);
            $layout = array_values(array_filter($layout, function($row) {
                return !empty($row);
            }));
            break;
            
        case 'newrow_below':
            unset($layout[$currentRow][$currentCol]);
            $layout[$currentRow] = array_values($layout[$currentRow]);
            array_splice($layout, $currentRow + 1, 0, [[$buttonIndex]]);
            $layout = array_values(array_filter($layout, function($row) {
                return !empty($row);
            }));
            break;
    }
    
    $data['settings']['button_layout'] = $layout;
    saveData($data);
    
    return true;
}

function sendButtonContent($chatId, $userId, $button, $contentIndex) {
    $content = $button['contents'][$contentIndex];
    $totalContents = count($button['contents']);
    
    $lastMessageIds = getUserLastMessages($userId);
    foreach ($lastMessageIds as $msgId) {
        deleteMessage($chatId, $msgId);
    }
    
    $navButtons = [];
    
    if ($contentIndex > 0) {
        $navButtons[] = ['text' => '⬅️ السابق', 'callback_data' => 'btn_' . $button['id'] . '_' . ($contentIndex - 1)];
    }
    
    if ($contentIndex < $totalContents - 1) {
        $navButtons[] = ['text' => '➡️ التالي', 'callback_data' => 'btn_' . $button['id'] . '_' . ($contentIndex + 1)];
    }
    
    $keyboard = null;
    if (isset($content['reply_markup']) && !empty($content['reply_markup'])) {
        $contentButtons = $content['reply_markup']['inline_keyboard'] ?? [];
        if (!empty($navButtons)) {
            $contentButtons[] = $navButtons;
        }
        $keyboard = ['inline_keyboard' => $contentButtons];
    } elseif (!empty($navButtons)) {
        $keyboard = ['inline_keyboard' => [$navButtons]];
    }
    
    $messageIds = [];
    
    if ($content['type'] == 'photo') {
        $caption = $content['caption'];
        if ($totalContents > 1) {
            $caption .= "\n\n📍 " . ($contentIndex + 1) . " من {$totalContents}";
        }
        $result = sendPhoto($chatId, $content['file_id'], $caption, $keyboard, null, $content['caption_entities'] ?? null);
        if ($result && isset($result['result']['message_id'])) {
            $messageIds[] = $result['result']['message_id'];
        }
    } elseif ($content['type'] == 'video') {
        $caption = $content['caption'];
        if ($totalContents > 1) {
            $caption .= "\n\n📍 " . ($contentIndex + 1) . " من {$totalContents}";
        }
        $result = sendVideo($chatId, $content['file_id'], $caption, $keyboard, null, $content['caption_entities'] ?? null);
        if ($result && isset($result['result']['message_id'])) {
            $messageIds[] = $result['result']['message_id'];
        }
    } elseif ($content['type'] == 'document') {
        $caption = $content['caption'];
        if ($totalContents > 1) {
            $caption .= "\n\n📍 " . ($contentIndex + 1) . " من {$totalContents}";
        }
        $result = sendDocument($chatId, $content['file_id'], $caption, $keyboard, null, $content['caption_entities'] ?? null);
        if ($result && isset($result['result']['message_id'])) {
            $messageIds[] = $result['result']['message_id'];
        }
    } elseif ($content['type'] == 'album') {
        $media = [];
        foreach ($content['photos'] as $idx => $photo) {
            $mediaItem = [
                'type' => 'photo',
                'media' => $photo['file_id']
            ];
            if ($idx == 0 && !empty($content['caption'])) {
                $caption = $content['caption'];
                if ($totalContents > 1) {
                    $caption .= "\n\n📍 " . ($contentIndex + 1) . " من {$totalContents}";
                }
                $mediaItem['caption'] = $caption;
                if (isset($content['caption_entities'])) {
                    $mediaItem['caption_entities'] = $content['caption_entities'];
                } else {
                    $mediaItem['parse_mode'] = 'Markdown';
                }
            }
            $media[] = $mediaItem;
        }
        $result = sendMediaGroup($chatId, $media);
        
        if ($result && isset($result['result']) && is_array($result['result'])) {
            foreach ($result['result'] as $msg) {
                if (isset($msg['message_id'])) {
                    $messageIds[] = $msg['message_id'];
                }
            }
        }
        
        if ($keyboard) {
            $result = sendMessage($chatId, "📍 " . ($contentIndex + 1) . " من {$totalContents}", $keyboard);
            if ($result && isset($result['result']['message_id'])) {
                $messageIds[] = $result['result']['message_id'];
            }
        }
    } else {
        $text = $content['content'];
        if ($totalContents > 1) {
            $text .= "\n\n📍 " . ($contentIndex + 1) . " من {$totalContents}";
        }
        $result = sendMessage($chatId, $text, $keyboard, null, $content['entities'] ?? null);
        if ($result && isset($result['result']['message_id'])) {
            $messageIds[] = $result['result']['message_id'];
        }
    }
    
    if (!empty($messageIds)) {
        setUserLastMessages($userId, $messageIds);
    }
}

// Get update from Telegram
$update = json_decode(file_get_contents('php://input'), true);

if (!$update) {
    exit;
}

// Process messages
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $userId = $message['from']['id'];
    $firstName = $message['from']['first_name'] ?? 'User';
    $username = $message['from']['username'] ?? null;
    $text = $message['text'] ?? '';
    
    registerUser($userId, $firstName, $username);
    
    $temp = loadTemp();
    $inWorkflow = isset($temp[$userId]) && (
        isset($temp[$userId]['step']) || 
        isset($temp[$userId]['adding']) ||
        isset($temp[$userId]['deleting'])
    );
    
    $forwardedFrom = isset($message['forward_from']) || isset($message['forward_from_chat']);
    $repliedTo = isset($message['reply_to_message']);
    
    if (strpos($text, '/start') === 0 || (!isAdmin($userId) && !$inWorkflow && empty($text))) {
        $data = loadData();
        
        if (isAdmin($userId)) {
            if (isset($temp[$userId])) {
                unset($temp[$userId]);
                saveTemp($temp);
            }
            
            $response = "🎛 *لوحة التحكم الرئيسية*\n\n";
            $response .= "مرحباً *{$firstName}*! 👋\n\n";
            $response .= "استخدم الأزرار أدناه للتحكم بالبوت:";
            
            sendMessage($chatId, $response, getAdminPanel());
        } else {
            $response = "مرحباً *{$firstName}* 👋\n\nأهلاً بك في البوت!";
            
            $keyboard = buildUserKeyboard($data);
            if ($keyboard) {
                $response .= "\n\nاختر من الأزرار أدناه:";
            }
            
            sendMessage($chatId, $response, $keyboard);
        }
    }
    
    elseif (strpos($text, '/preview') === 0 && isAdmin($userId)) {
        $data = loadData();
        
        if (empty($data['buttons'])) {
            sendMessage($chatId, '❌ لا توجد أزرار للمعاينة.');
        } else {
            $response = "📱 *معاينة للمستخدمين:*\n\nهكذا سيظهر البوت للمستخدمين العاديين:";
            $keyboard = buildUserKeyboard($data);
            sendMessage($chatId, $response, $keyboard);
        }
    }
    
    elseif (strtolower(trim($text)) == 'done' && isAdmin($userId) && isset($temp[$userId]['adding'])) {
        // Check for pending albums and finalize them
        if (isset($temp[$userId]['album_temp']) && !empty($temp[$userId]['album_temp'])) {
            foreach ($temp[$userId]['album_temp'] as $mediaGroupId => $albumData) {
                $photoCount = count($albumData['photos']);
                
                if ($photoCount >= 2) {
                    $temp[$userId]['contents'][] = [
                        'type' => 'album',
                        'media_group_id' => $mediaGroupId,
                        'photos' => $albumData['photos'],
                        'caption' => $albumData['caption'],
                        'caption_entities' => $albumData['caption_entities'] ?? null,
                        'reply_markup' => $albumData['reply_markup']
                    ];
                    
                    // Update progress message to show completion
                    if (isset($albumData['progress_msg_id'])) {
                        $contentNum = count($temp[$userId]['contents']);
                        editMessageText($chatId, $albumData['progress_msg_id'], "✅ تم إضافة المحتوى #{$contentNum} (ألبوم {$photoCount} صور)\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
                    }
                }
            }
            unset($temp[$userId]['album_temp']);
            saveTemp($temp);
        }
        
        $temp = loadTemp();
        
        if (isset($temp[$userId]['contents']) && !empty($temp[$userId]['contents'])) {
            $data = loadData();
            
            $newButton = [
                'id' => uniqid(),
                'name' => $temp[$userId]['name'],
                'contents' => $temp[$userId]['contents'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $data['buttons'][] = $newButton;
            
            $newButtonIndex = count($data['buttons']) - 1;
            if (empty($data['settings']['button_layout'])) {
                $data['settings']['button_layout'] = [];
                foreach ($data['buttons'] as $idx => $btn) {
                    $data['settings']['button_layout'][] = [$idx];
                }
            } else {
                $data['settings']['button_layout'][] = [$newButtonIndex];
            }
            
            saveData($data);
            
            $contentCount = count($temp[$userId]['contents']);
            unset($temp[$userId]);
            saveTemp($temp);
            
            sendMessage($chatId, "✅ *تم إنشاء الزر بنجاح!*\n\n📌 الاسم: *{$newButton['name']}*\n📦 عدد المحتويات: {$contentCount}\n🆔 ID: `{$newButton['id']}`\n\n✨ تم إضافته للترتيب تلقائياً");
        } else {
            sendMessage($chatId, '❌ لم تضف أي محتوى بعد! أرسل محتوى أولاً.');
        }
    }
    
    elseif (!empty($text) && strpos($text, '/') !== 0 && isAdmin($userId)) {
        if (isset($temp[$userId]['step']) && $temp[$userId]['step'] == 'waiting_name') {
            $temp[$userId] = [
                'name' => $text,
                'contents' => [],
                'adding' => true
            ];
            saveTemp($temp);
            
            sendMessage($chatId, "📦 *الآن أرسل المحتويات:*\n\n• نصوص\n• صور (مع أزرار)\n• فيديوهات\n• ملفات\n• ألبوم صور (2-10 صور)\n• رسائل موجهة أو مقتبسة\n\n✍️ اكتب *done* عند الانتهاء.");
        }
        elseif (isset($temp[$userId]['adding']) && !$forwardedFrom && !$repliedTo) {
            $entities = $message['entities'] ?? null;
            
            $temp[$userId]['contents'][] = [
                'type' => 'text',
                'content' => $text,
                'entities' => $entities
            ];
            saveTemp($temp);
            
            $count = count($temp[$userId]['contents']);
            sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
        }
        elseif (isset($temp[$userId]['deleting'])) {
            $data = loadData();
            
            $text = str_replace([',', "\n"], ' ', $text);
            $numbers = array_filter(array_map('trim', explode(' ', $text)));
            $numbersToDelete = array_map(function($n) { return (int)$n - 1; }, $numbers);
            
            $deletedNames = [];
            $numbersToDelete = array_unique($numbersToDelete);
            rsort($numbersToDelete);
            
            foreach ($numbersToDelete as $buttonIndex) {
                if (isset($data['buttons'][$buttonIndex])) {
                    $deletedNames[] = $data['buttons'][$buttonIndex]['name'];
                    array_splice($data['buttons'], $buttonIndex, 1);
                }
            }
            
            if (!empty($deletedNames)) {
                $data['settings']['button_layout'] = [];
                foreach ($data['buttons'] as $idx => $btn) {
                    $data['settings']['button_layout'][] = [$idx];
                }
                
                saveData($data);
                
                unset($temp[$userId]);
                saveTemp($temp);
                
                $deletedCount = count($deletedNames);
                $deletedList = implode("\n• ", $deletedNames);
                sendMessage($chatId, "✅ *تم حذف {$deletedCount} زر بنجاح!*\n\n🗑 الأزرار المحذوفة:\n• {$deletedList}");
            } else {
                sendMessage($chatId, '❌ أرقام غير صحيحة! حاول مرة أخرى.');
            }
        }
    }
    
    elseif (!empty($text) && strpos($text, '/') !== 0 && !isAdmin($userId)) {
        $data = loadData();
        $found = false;
        
        foreach ($data['buttons'] as $button) {
            if ($button['name'] == $text) {
                $found = true;
                if (isset($button['contents'][0])) {
                    sendButtonContent($chatId, $userId, $button, 0);
                }
                break;
            }
        }
        
        if (!$found) {
            $response = "مرحباً *{$firstName}* 👋\n\nاختر من الأزرار:";
            $keyboard = buildUserKeyboard($data);
            sendMessage($chatId, $response, $keyboard);
        }
    }
    
    elseif (($forwardedFrom || $repliedTo) && isAdmin($userId) && isset($temp[$userId]['adding'])) {
        $sourceMessage = $repliedTo ? $message['reply_to_message'] : $message;
        
        if (isset($sourceMessage['photo'])) {
            $photo = end($sourceMessage['photo']);
            $fileId = $photo['file_id'];
            $caption = $sourceMessage['caption'] ?? '';
            $captionEntities = $sourceMessage['caption_entities'] ?? null;
            $replyMarkup = isset($sourceMessage['reply_markup']) ? $sourceMessage['reply_markup'] : null;
            
            if (isset($sourceMessage['media_group_id'])) {
                $mediaGroupId = $sourceMessage['media_group_id'];
                
                if (!isset($temp[$userId]['album_temp'])) {
                    $temp[$userId]['album_temp'] = [];
                }
                
                if (!isset($temp[$userId]['album_temp'][$mediaGroupId])) {
                    $temp[$userId]['album_temp'][$mediaGroupId] = [
                        'photos' => [],
                        'caption' => $caption,
                        'caption_entities' => $captionEntities,
                        'reply_markup' => $replyMarkup,
                        'created_at' => time(),
                        'progress_msg_id' => null
                    ];
                }
                
                $temp[$userId]['album_temp'][$mediaGroupId]['photos'][] = ['file_id' => $fileId];
                $photoCount = count($temp[$userId]['album_temp'][$mediaGroupId]['photos']);
                
                if ($photoCount == 1) {
                    $result = sendMessage($chatId, "📸 جاري جمع الصور: {$photoCount} صورة...");
                    if ($result && isset($result['result']['message_id'])) {
                        $temp[$userId]['album_temp'][$mediaGroupId]['progress_msg_id'] = $result['result']['message_id'];
                    }
                } else {
                    if (isset($temp[$userId]['album_temp'][$mediaGroupId]['progress_msg_id'])) {
                        editMessageText($chatId, $temp[$userId]['album_temp'][$mediaGroupId]['progress_msg_id'], "📸 جاري جمع الصور: {$photoCount} صور...");
                    }
                }
                
                saveTemp($temp);
                
            } else {
                $temp[$userId]['contents'][] = [
                    'type' => 'photo',
                    'file_id' => $fileId,
                    'caption' => $caption,
                    'caption_entities' => $captionEntities,
                    'reply_markup' => $replyMarkup
                ];
                saveTemp($temp);
                
                $count = count($temp[$userId]['contents']);
                $markupNote = $replyMarkup ? ' (مع أزرار)' : '';
                sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}{$markupNote}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
            }
        }
        elseif (isset($sourceMessage['video'])) {
            $fileId = $sourceMessage['video']['file_id'];
            $caption = $sourceMessage['caption'] ?? '';
            $captionEntities = $sourceMessage['caption_entities'] ?? null;
            $replyMarkup = isset($sourceMessage['reply_markup']) ? $sourceMessage['reply_markup'] : null;
            
            $temp[$userId]['contents'][] = [
                'type' => 'video',
                'file_id' => $fileId,
                'caption' => $caption,
                'caption_entities' => $captionEntities,
                'reply_markup' => $replyMarkup
            ];
            saveTemp($temp);
            
            $count = count($temp[$userId]['contents']);
            $markupNote = $replyMarkup ? ' (مع أزرار)' : '';
            sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}{$markupNote}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
        }
        elseif (isset($sourceMessage['document'])) {
            $fileId = $sourceMessage['document']['file_id'];
            $caption = $sourceMessage['caption'] ?? '';
            $captionEntities = $sourceMessage['caption_entities'] ?? null;
            $replyMarkup = isset($sourceMessage['reply_markup']) ? $sourceMessage['reply_markup'] : null;
            
            $temp[$userId]['contents'][] = [
                'type' => 'document',
                'file_id' => $fileId,
                'caption' => $caption,
                'caption_entities' => $captionEntities,
                'reply_markup' => $replyMarkup
            ];
            saveTemp($temp);
            
            $count = count($temp[$userId]['contents']);
            $markupNote = $replyMarkup ? ' (مع أزرار)' : '';
            sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}{$markupNote}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
        }
        elseif (isset($sourceMessage['text'])) {
            $sourceText = $sourceMessage['text'];
            $entities = $sourceMessage['entities'] ?? null;
            
            $temp[$userId]['contents'][] = [
                'type' => 'text',
                'content' => $sourceText,
                'entities' => $entities
            ];
            saveTemp($temp);
            
            $count = count($temp[$userId]['contents']);
            sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
        }
    }
    
    elseif (isset($message['photo']) && isAdmin($userId) && isset($temp[$userId]['adding'])) {
        $photo = end($message['photo']);
        $fileId = $photo['file_id'];
        $caption = $message['caption'] ?? '';
        $captionEntities = $message['caption_entities'] ?? null;
        $replyMarkup = isset($message['reply_markup']) ? $message['reply_markup'] : null;
        
        if (isset($message['media_group_id'])) {
            $mediaGroupId = $message['media_group_id'];
            
            if (!isset($temp[$userId]['album_temp'])) {
                $temp[$userId]['album_temp'] = [];
            }
            
            if (!isset($temp[$userId]['album_temp'][$mediaGroupId])) {
                $temp[$userId]['album_temp'][$mediaGroupId] = [
                    'photos' => [],
                    'caption' => $caption,
                    'caption_entities' => $captionEntities,
                    'reply_markup' => $replyMarkup,
                    'created_at' => time(),
                    'progress_msg_id' => null
                ];
            }
            
            $temp[$userId]['album_temp'][$mediaGroupId]['photos'][] = ['file_id' => $fileId];
            $photoCount = count($temp[$userId]['album_temp'][$mediaGroupId]['photos']);
            
            // Send progress message on first photo, update on subsequent ones
            if ($photoCount == 1) {
                $result = sendMessage($chatId, "📸 جاري جمع الصور: {$photoCount} صورة...");
                if ($result && isset($result['result']['message_id'])) {
                    $temp[$userId]['album_temp'][$mediaGroupId]['progress_msg_id'] = $result['result']['message_id'];
                }
            } else {
                if (isset($temp[$userId]['album_temp'][$mediaGroupId]['progress_msg_id'])) {
                    editMessageText($chatId, $temp[$userId]['album_temp'][$mediaGroupId]['progress_msg_id'], "📸 جاري جمع الصور: {$photoCount} صور...");
                }
            }
            
            saveTemp($temp);
            
        } else {
            $temp[$userId]['contents'][] = [
                'type' => 'photo',
                'file_id' => $fileId,
                'caption' => $caption,
                'caption_entities' => $captionEntities,
                'reply_markup' => $replyMarkup
            ];
            saveTemp($temp);
            
            $count = count($temp[$userId]['contents']);
            $markupNote = $replyMarkup ? ' (مع أزرار)' : '';
            sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}{$markupNote}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
        }
    }
    
    elseif (isset($message['video']) && isAdmin($userId) && isset($temp[$userId]['adding'])) {
        $fileId = $message['video']['file_id'];
        $caption = $message['caption'] ?? '';
        $captionEntities = $message['caption_entities'] ?? null;
        $replyMarkup = isset($message['reply_markup']) ? $message['reply_markup'] : null;
        
        $temp[$userId]['contents'][] = [
            'type' => 'video',
            'file_id' => $fileId,
            'caption' => $caption,
            'caption_entities' => $captionEntities,
            'reply_markup' => $replyMarkup
        ];
        saveTemp($temp);
        
        $count = count($temp[$userId]['contents']);
        $markupNote = $replyMarkup ? ' (مع أزرار)' : '';
        sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}{$markupNote}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
    }
    
    elseif (isset($message['document']) && isAdmin($userId) && isset($temp[$userId]['adding'])) {
        $fileId = $message['document']['file_id'];
        $caption = $message['caption'] ?? '';
        $captionEntities = $message['caption_entities'] ?? null;
        $replyMarkup = isset($message['reply_markup']) ? $message['reply_markup'] : null;
        
        $temp[$userId]['contents'][] = [
            'type' => 'document',
            'file_id' => $fileId,
            'caption' => $caption,
            'caption_entities' => $captionEntities,
            'reply_markup' => $replyMarkup
        ];
        saveTemp($temp);
        
        $count = count($temp[$userId]['contents']);
        $markupNote = $replyMarkup ? ' (مع أزرار)' : '';
        sendMessage($chatId, "✅ تم إضافة المحتوى #{$count}{$markupNote}\n\n💡 أرسل المزيد من المحتويات أو اكتب *done* للإنهاء.");
    }
}

// Handle callbacks
elseif (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $callbackId = $callback['id'];
    $chatId = $callback['message']['chat']['id'];
    $messageId = $callback['message']['message_id'];
    $callbackData = $callback['data'];
    $userId = $callback['from']['id'];
    
    $data = loadData();
    $temp = loadTemp();
    
    if ($callbackData == 'admin_add') {
        answerCallbackQuery($callbackId);
        
        $response = "➕ *إضافة زر جديد*\n\n";
        $response .= "📝 أرسل *اسم الزر* الآن:";
        
        $temp[$userId] = ['step' => 'waiting_name'];
        saveTemp($temp);
        
        editMessageText($chatId, $messageId, $response);
    }
    
    elseif ($callbackData == 'admin_list') {
        answerCallbackQuery($callbackId);
        
        if (empty($data['buttons'])) {
            editMessageText($chatId, $messageId, '❌ لا توجد أزرار حالياً.', getAdminPanel());
        } else {
            $response = "📋 *قائمة الأزرار:*\n\n";
            foreach ($data['buttons'] as $index => $button) {
                $num = $index + 1;
                $contentCount = count($button['contents']);
                $response .= "{$num}. *{$button['name']}*\n";
                $response .= "   📦 المحتويات: {$contentCount}\n";
                $response .= "   🆔 ID: `{$button['id']}`\n\n";
            }
            
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🗑 حذف أزرار', 'callback_data' => 'admin_delete']],
                    [['text' => '🔙 رجوع', 'callback_data' => 'admin_back']]
                ]
            ];
            
            editMessageText($chatId, $messageId, $response, $keyboard);
        }
    }
    
    elseif ($callbackData == 'admin_delete') {
        answerCallbackQuery($callbackId);
        
        if (empty($data['buttons'])) {
            editMessageText($chatId, $messageId, '❌ لا توجد أزرار للحذف!', getAdminPanel());
        } else {
            $response = "🗑 *حذف أزرار*\n\n";
            $response .= "قائمة الأزرار:\n\n";
            
            foreach ($data['buttons'] as $index => $button) {
                $num = $index + 1;
                $response .= "{$num}. {$button['name']}\n";
            }
            
            $response .= "\n📝 أرسل الأرقام للحذف:\n";
            $response .= "مثال: `1 3 5` أو `1,3,5` أو كل رقم في سطر";
            
            $temp[$userId] = ['deleting' => true];
            saveTemp($temp);
            
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '❌ إلغاء', 'callback_data' => 'admin_back']]
                ]
            ];
            
            editMessageText($chatId, $messageId, $response, $keyboard);
        }
    }
    
    elseif ($callbackData == 'admin_stats') {
        answerCallbackQuery($callbackId);
        
        $users = loadUsers();
        $totalUsers = $users['stats']['total'];
        $buttonCount = count($data['buttons']);
        
        $activeCount = 0;
        $weekAgo = strtotime('-7 days');
        foreach ($users['users'] as $user) {
            if (strtotime($user['last_active']) > $weekAgo) {
                $activeCount++;
            }
        }
        
        $response = "📊 *إحصائيات البوت*\n\n";
        $response .= "👥 إجمالي الأعضاء: *{$totalUsers}*\n";
        $response .= "✅ النشطين (7 أيام): *{$activeCount}*\n";
        $response .= "🔘 عدد الأزرار: *{$buttonCount}*\n\n";
        $response .= "📅 تاريخ اليوم: " . date('Y-m-d');
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '🔙 رجوع', 'callback_data' => 'admin_back']]
            ]
        ];
        
        editMessageText($chatId, $messageId, $response, $keyboard);
    }
    
    elseif ($callbackData == 'admin_layout') {
        answerCallbackQuery($callbackId);
        
        $layoutInfo = getLayoutManager($data);
        editMessageText($chatId, $messageId, $layoutInfo['text'], $layoutInfo['keyboard']);
    }
    
    elseif (strpos($callbackData, 'layout_btn_') === 0) {
        answerCallbackQuery($callbackId);
        
        $buttonIndex = (int)str_replace('layout_btn_', '', $callbackData);
        $controlsInfo = getButtonArrowControls($buttonIndex, $data);
        editMessageText($chatId, $messageId, $controlsInfo['text'], $controlsInfo['keyboard']);
    }
    
    elseif (strpos($callbackData, 'move_') === 0) {
        $parts = explode('_', $callbackData);
        $buttonIndex = (int)$parts[1];
        
        if (isset($parts[3])) {
            $direction = $parts[2] . '_' . $parts[3];
        } else {
            $direction = $parts[2];
        }
        
        $success = moveButton($buttonIndex, $direction, $data);
        
        if ($success) {
            answerCallbackQuery($callbackId, '✅ تم التحريك!');
            
            $layoutInfo = getLayoutManager($data);
            editMessageText($chatId, $messageId, $layoutInfo['text'], $layoutInfo['keyboard']);
        } else {
            answerCallbackQuery($callbackId, '❌ لا يمكن التحريك في هذا الاتجاه!', true);
        }
    }
    
    elseif ($callbackData == 'layout_clear') {
        answerCallbackQuery($callbackId, '✅ تم مسح الترتيب');
        
        $data['settings']['button_layout'] = [];
        saveData($data);
        
        $layoutInfo = getLayoutManager($data);
        editMessageText($chatId, $messageId, $layoutInfo['text'], $layoutInfo['keyboard']);
    }
    
    elseif ($callbackData == 'layout_save') {
        answerCallbackQuery($callbackId, '✅ تم حفظ الترتيب!', true);
        
        $response = "✅ *تم حفظ الترتيب!*\n\n";
        $response .= "استخدم /preview لمعاينة النتيجة";
        
        editMessageText($chatId, $messageId, $response, getAdminPanel());
    }
    
    elseif ($callbackData == 'admin_preview') {
        answerCallbackQuery($callbackId, '✅ تم إرسال المعاينة!');
        
        if (empty($data['buttons'])) {
            answerCallbackQuery($callbackId, '❌ لا توجد أزرار للمعاينة!', true);
        } else {
            $response = "📱 *معاينة للمستخدمين:*\n\nهكذا سيظهر البوت للمستخدمين العاديين:";
            $keyboard = buildUserKeyboard($data);
            sendMessage($chatId, $response, $keyboard);
        }
    }
    
    elseif ($callbackData == 'admin_back') {
        answerCallbackQuery($callbackId);
        
        if (isset($temp[$userId])) {
            unset($temp[$userId]);
            saveTemp($temp);
        }
        
        $response = "🎛 *لوحة التحكم الرئيسية*\n\n";
        $response .= "استخدم الأزرار أدناه للتحكم بالبوت:";
        
        editMessageText($chatId, $messageId, $response, getAdminPanel());
    }
    
    elseif (strpos($callbackData, 'btn_') === 0) {
        $parts = explode('_', $callbackData);
        $buttonId = $parts[1];
        $contentIndex = isset($parts[2]) ? (int)$parts[2] : 0;
        
        foreach ($data['buttons'] as $button) {
            if ($button['id'] == $buttonId) {
                if (isset($button['contents'][$contentIndex])) {
                    answerCallbackQuery($callbackId);
                    sendButtonContent($chatId, $userId, $button, $contentIndex);
                }
                break;
            }
        }
    }
}
