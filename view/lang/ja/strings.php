<?php

if(! function_exists("string_plural_select_ja")) {
function string_plural_select_ja($n){
	$n = intval($n);
	return intval(0);
}}
$a->strings['Unable to locate original post.'] = '元の投稿が見つかりません。';
$a->strings['Post updated.'] = '投稿が更新されました。';
$a->strings['Item wasn\'t stored.'] = '項目が保存されませんでした。';
$a->strings['Item couldn\'t be fetched.'] = '項目を取得できませんでした。';
$a->strings['Empty post discarded.'] = '空の投稿は破棄されました。';
$a->strings['Item not found.'] = '見つかりませんでした。';
$a->strings['Permission denied.'] = '必要な権限が有りません。';
$a->strings['No valid account found.'] = '有効なアカウントが見つかりません。';
$a->strings['Password reset request issued. Check your email.'] = 'パスワードリセット要求が発行されました。あなたのメールをチェックしてください。';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		%1$s さん、
			"%2$s" アカウントのパスワードリセットが要求されました。
		このリクエストを確認するには、確認リンクをクリックするか、
		ウェブブラウザのアドレスバーに貼り付けてください。

		この変更をリクエストしていない場合は、リンクをクリックせず、
		このメールを無視・削除してください。リセットはキャンセルされます。

		このリクエストの発行元があなたであると確認できない限り、
		パスワードは変更されません。';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		このリンクをたどって本人確認を行ってください：

		%1$s

		新しいパスワードを含むフォローアップメッセージが届きます。
		ログイン後にアカウント設定ページからそのパスワードを変更できます。

		ログインの詳細は次のとおりです。

		サイトの場所：	%2$s
		ログイン名：	%3$s';
$a->strings['Password reset requested at %s'] = 'パスワードのリセット要求が有りました:  %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'リクエストを確認できませんでした。 （以前に送信した可能性があります。）パスワードのリセットに失敗しました。';
$a->strings['Request has expired, please make a new one.'] = 'リクエストの有効期限が切れています。新しいものを作成してください。';
$a->strings['Forgot your Password?'] = 'パスワードをお忘れですか？';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'メールアドレスを入力して送信し、パスワードをリセットしてください。その後、メールで詳細な手順を確認してください。';
$a->strings['Nickname or Email: '] = 'ニックネームまたはメール：';
$a->strings['Reset'] = 'リセットする';
$a->strings['Password Reset'] = 'パスワードのリセット';
$a->strings['Your password has been reset as requested.'] = 'パスワードは要求どおりにリセットされました。';
$a->strings['Your new password is'] = '新しいパスワードは';
$a->strings['Save or copy your new password - and then'] = '新しいパスワードを保存またはコピーします-その後';
$a->strings['click here to login'] = 'ここをクリックしてログイン';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'ログインに成功すると、パスワードは<em>設定</em>ページから変更される場合があります。';
$a->strings['Your password has been reset.'] = 'パスワードはリセットされました。';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			%1$s さん、
				パスワードは要求に応じて変更されました。記録のためにこの情報を保管してください（または、パスワードをすぐに覚えているものに変更してください）。
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			ログインの詳細は次のとおりです：

			サイトの場所：	%1$s
			ログイン名：	%2$s
			パスワード：	%3$s

			ログイン後にアカウント設定ページからパスワードを変更できます。
		';
$a->strings['Your password has been changed at %s'] = 'パスワードは%s変更されました';
$a->strings['New Message'] = '新しいメッセージ';
$a->strings['No recipient selected.'] = '宛先が未指定です。';
$a->strings['Unable to locate contact information.'] = 'コンタクト情報が見つかりません。';
$a->strings['Message could not be sent.'] = 'メッセージを送信できませんでした。';
$a->strings['Message collection failure.'] = 'メッセージの収集に失敗しました。';
$a->strings['Discard'] = '捨てる';
$a->strings['Messages'] = 'メッセージ';
$a->strings['Conversation not found.'] = '会話が見つかりません。';
$a->strings['Message was not deleted.'] = 'メッセージを削除しませんでした。';
$a->strings['Conversation was not removed.'] = '会話を削除しませんでした。';
$a->strings['Please enter a link URL:'] = 'リンクURLを入力してください。';
$a->strings['Send Private Message'] = 'プライベートメッセージを送信する';
$a->strings['To:'] = '送信先:';
$a->strings['Subject:'] = '件名';
$a->strings['Your message:'] = 'メッセージ';
$a->strings['Upload photo'] = '写真をアップロード';
$a->strings['Insert web link'] = 'webリンクを挿入';
$a->strings['Please wait'] = 'お待ち下さい';
$a->strings['Submit'] = '送信する';
$a->strings['No messages.'] = 'メッセージはありません。';
$a->strings['Message not available.'] = 'メッセージは利用できません。';
$a->strings['Delete message'] = 'メッセージを削除';
$a->strings['D, d M Y - g:i A'] = 'D、d MY-g：i A';
$a->strings['Delete conversation'] = '会話を削除';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = '安全な通信は利用できません。送信者のプロフィールページから返信できる<strong>場合が</strong>あります。';
$a->strings['Send Reply'] = '返信する';
$a->strings['Unknown sender - %s'] = '不明な送信者です - %s';
$a->strings['You and %s'] = 'あなたと%s';
$a->strings['%s and You'] = '%sとあなた';
$a->strings['%d message'] = [
	0 => '%dメッセージ',
];
$a->strings['Personal Notes'] = '個人メモ';
$a->strings['Personal notes are visible only by yourself.'] = '個人メモは自分自身によってのみ見えます。';
$a->strings['Save'] = '保存する';
$a->strings['User not found.'] = 'ユーザーが見つかりません。';
$a->strings['Photo Albums'] = 'フォトアルバム';
$a->strings['Recent Photos'] = '最近の写真';
$a->strings['Upload New Photos'] = '新しい写真をアップロード';
$a->strings['everybody'] = 'みなさん';
$a->strings['Contact information unavailable'] = 'コンタクト情報は利用できません';
$a->strings['Album not found.'] = 'アルバムが見つかりません。';
$a->strings['Album successfully deleted'] = 'アルバムを削除しました';
$a->strings['Album was empty.'] = 'アルバムは空でした。';
$a->strings['Failed to delete the photo.'] = '写真を削除できませんでした';
$a->strings['a photo'] = '写真';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$sが%2$sで%3$sによってタグ付けされました';
$a->strings['Public access denied.'] = 'パブリックアクセスが拒否されました。';
$a->strings['No photos selected'] = '写真が選択されていません';
$a->strings['Upload Photos'] = '写真をアップロードする';
$a->strings['New album name: '] = '新しいアルバム名：';
$a->strings['or select existing album:'] = 'または既存のアルバムを選択：';
$a->strings['Do not show a status post for this upload'] = 'このアップロードのステータス投稿を表示しません';
$a->strings['Permissions'] = '許可';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'このフォトアルバムとそのすべての写真を本当に削除しますか？';
$a->strings['Delete Album'] = 'アルバムを削除';
$a->strings['Cancel'] = 'キャンセル';
$a->strings['Edit Album'] = 'アルバムを編集';
$a->strings['Drop Album'] = 'アルバムを削除';
$a->strings['Show Newest First'] = '新しいもの順に表示';
$a->strings['Show Oldest First'] = '最も古いものを最初に表示';
$a->strings['View Photo'] = '写真を見る';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'アクセス拒否。この項目へのアクセスは制限される場合があります。';
$a->strings['Photo not available'] = '写真は利用できません';
$a->strings['Do you really want to delete this photo?'] = 'この写真を本当に削除しますか？';
$a->strings['Delete Photo'] = '写真を削除';
$a->strings['View photo'] = '写真を見る';
$a->strings['Edit photo'] = '写真を編集する';
$a->strings['Delete photo'] = '写真を削除';
$a->strings['Use as profile photo'] = 'プロフィール写真として使用';
$a->strings['Private Photo'] = 'プライベート写真';
$a->strings['View Full Size'] = 'フルサイズを表示';
$a->strings['Tags: '] = 'タグ：';
$a->strings['[Select tags to remove]'] = '[削除するタグを選択]';
$a->strings['New album name'] = '新しいアルバム名';
$a->strings['Caption'] = 'キャプション';
$a->strings['Add a Tag'] = 'タグを追加する';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = '例：@ bob、@ Barbara_Jensen、@ jim @ example.com、＃California、＃camping';
$a->strings['Do not rotate'] = '回転させないでください';
$a->strings['Rotate CW (right)'] = 'CWを回転（右）';
$a->strings['Rotate CCW (left)'] = 'CCWを回転（左）';
$a->strings['This is you'] = 'これはあなたです';
$a->strings['Comment'] = 'コメント';
$a->strings['Preview'] = 'プレビュー';
$a->strings['Loading...'] = '読み込み中…';
$a->strings['Select'] = '選択';
$a->strings['Delete'] = '削除';
$a->strings['Like'] = 'いいね';
$a->strings['I like this (toggle)'] = '私はこれが好きです（トグル）';
$a->strings['Dislike'] = '嫌い';
$a->strings['I don\'t like this (toggle)'] = '気に入らない（トグル）';
$a->strings['Map'] = '地図';
$a->strings['Delete this item?'] = 'この項目を削除しますか？';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'この作者をブロックしますか？その人はあなたをフォローできなくなり、あなたの公開された投稿を見ることができなくなります。また、あなたはその人の投稿や通知を見ることができなくなります。';
$a->strings['toggle mobile'] = 'モバイルを切り替え';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'そのメソッドは、このモジュールでは許可されていません。 このメソッド(たち)が許可されています: %s';
$a->strings['Page not found.'] = 'ページが見つかりません。';
$a->strings['You must be logged in to use addons. '] = 'アドオンを使用するにはログインする必要があります。';
$a->strings['No system theme config value set.'] = 'システムテーマの構成値が設定されていません。';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'フォームセキュリティトークンが正しくありませんでした。これは、フォームを送信する前にフォームが長時間（3時間以上）開かれたために発生した可能性があります。';
$a->strings['All contacts'] = 'すべてのコンタクト';
$a->strings['Followers'] = 'フォロワー';
$a->strings['Following'] = 'フォロー中';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'このURL（ %s ）のアーカイブされていないコンタクトエントリが見つかりませんでした';
$a->strings['The contact entries have been archived'] = 'コンタクトエントリがアーカイブされました';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'このURL（ %s ）のコンタクトエントリが見つかりませんでした';
$a->strings['The contact has been blocked from the node'] = 'このコンタクトはノードからブロックされています';
$a->strings['Post update version number has been set to %s.'] = '更新後のバージョン番号が %s に設定されました。';
$a->strings['Check for pending update actions.'] = '保留中の更新アクションを確認します。';
$a->strings['Done.'] = '完了しました。';
$a->strings['Execute pending post updates.'] = '保留中の投稿の更新を実行します。';
$a->strings['All pending post updates are done.'] = '保留中の投稿の更新はすべて完了しました。';
$a->strings['User not found'] = 'ユーザーが見つかりません';
$a->strings['Enter new password: '] = '新しいパスワードを入力してください：';
$a->strings['Password update failed. Please try again.'] = 'パスワードの更新に失敗しました。もう一度試してください。';
$a->strings['Password changed.'] = 'パスワード変更済み。';
$a->strings['newer'] = '新しい';
$a->strings['older'] = '過去の';
$a->strings['Frequently'] = '頻度の高い';
$a->strings['Hourly'] = '毎時';
$a->strings['Twice daily'] = '1日2回';
$a->strings['Daily'] = '毎日';
$a->strings['Weekly'] = '毎週';
$a->strings['Monthly'] = '毎月';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS / Atom';
$a->strings['Email'] = 'Eメール';
$a->strings['Diaspora'] = 'ディアスポラ';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP / IM';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Diaspora コネクタ';
$a->strings['GNU Social Connector'] = 'GNU Social Connector';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['%s (via %s)'] = '%s (経由: %s)';
$a->strings['and'] = 'と';
$a->strings['and %d other people'] = 'と他 %d 人';
$a->strings['Visible to <strong>everybody</strong>'] = '<strong>すべての人</strong> が閲覧可能です';
$a->strings['Please enter a image/video/audio/webpage URL:'] = '画像/動画/音声/ウェブページのURLを入力してください:';
$a->strings['Tag term:'] = '用語のタグ付け:';
$a->strings['Where are you right now?'] = 'どこにいますか？:';
$a->strings['Delete item(s)?'] = 'これ(ら)の項目を削除しますか？';
$a->strings['New Post'] = '新しい投稿';
$a->strings['Share'] = '共有';
$a->strings['upload photo'] = '写真をアップロード';
$a->strings['Attach file'] = 'ファイルを添付';
$a->strings['attach file'] = 'ファイルを添付';
$a->strings['Bold'] = '太字';
$a->strings['Italic'] = '斜体';
$a->strings['Underline'] = '下線';
$a->strings['Quote'] = '引用';
$a->strings['Code'] = 'コード';
$a->strings['Image'] = '画像';
$a->strings['Link'] = 'リンク';
$a->strings['Link or Media'] = 'リンク／メディア';
$a->strings['Video'] = '動画';
$a->strings['Set your location'] = '現在地を設定';
$a->strings['set location'] = '現在地を設定';
$a->strings['Clear browser location'] = 'ブラウザの現在地を解除';
$a->strings['clear location'] = '現在地を解除';
$a->strings['Set title'] = '件名を設定';
$a->strings['Categories (comma-separated list)'] = 'カテゴリ（半角カンマ区切り）';
$a->strings['Permission settings'] = '権限設定';
$a->strings['Public post'] = '一般公開の投稿';
$a->strings['Message'] = 'メッセージ';
$a->strings['Browser'] = 'ブラウザ';
$a->strings['Open Compose page'] = '作成ページを開く';
$a->strings['remove'] = '削除';
$a->strings['Delete Selected Items'] = '選択した項目を削除';
$a->strings['%s reshared this.'] = '%s が再共有しました。';
$a->strings['Pinned item'] = 'ピン留め項目';
$a->strings['View %s\'s profile @ %s'] = '%sのプロフィールを確認 @ %s';
$a->strings['Categories:'] = 'カテゴリ:';
$a->strings['Filed under:'] = '格納先:';
$a->strings['%s from %s'] = '%s から %s';
$a->strings['View in context'] = '文脈で表示する';
$a->strings['Local Community'] = 'ローカル コミュニティ';
$a->strings['Posts from local users on this server'] = 'このサーバー上のローカルユーザーからの投稿';
$a->strings['Global Community'] = 'グローバルコミュニティ';
$a->strings['Posts from users of the whole federated network'] = 'フェデレーションネットワーク全体のユーザーからの投稿';
$a->strings['Latest Activity'] = '最近の操作';
$a->strings['Sort by latest activity'] = '最終更新順に並び替え';
$a->strings['Latest Posts'] = '最新の投稿';
$a->strings['Sort by post received date'] = '投稿を受信した順に並び替え';
$a->strings['Personal'] = 'パーソナル';
$a->strings['Posts that mention or involve you'] = 'あなたに言及または関与している投稿';
$a->strings['Starred'] = 'スター付き';
$a->strings['Favourite Posts'] = 'お気に入りの投稿';
$a->strings['General Features'] = '一般的な機能';
$a->strings['Photo Location'] = '写真の場所';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = '通常、写真のメタデータは削除されます。これにより、メタデータを除去する前に場所（存在する場合）が抽出され、マップにリンクされます。';
$a->strings['Post Composition Features'] = '合成後の機能';
$a->strings['Explicit Mentions'] = '明示的な言及';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'コメントボックスに明示的なメンションを追加して、返信の通知先をカスタマイズします。';
$a->strings['Post/Comment Tools'] = '投稿/コメントツール';
$a->strings['Post Categories'] = '投稿カテゴリ';
$a->strings['Add categories to your posts'] = '投稿にカテゴリを追加する';
$a->strings['Archives'] = 'アーカイブ';
$a->strings['Protocols'] = 'プロトコル';
$a->strings['Account Types'] = 'アカウントの種類';
$a->strings['Saved Searches'] = '保存された検索';
$a->strings['Saved Folders'] = '保存されたフォルダー';
$a->strings['Trending Tags'] = 'トレンドタグ';
$a->strings['Advanced Profile Settings'] = '高度なプロフィール設定';
$a->strings['Tag Cloud'] = 'タグクラウド';
$a->strings['Provide a personal tag cloud on your profile page'] = 'プロフィールページで個人タグクラウドを提供する';
$a->strings['Display Membership Date'] = '会員日を表示する';
$a->strings['Display membership date in profile'] = 'プロフィールにメンバーシップ日を表示する';
$a->strings['show more'] = 'もっと見せる';
$a->strings['event'] = 'イベント';
$a->strings['status'] = 'ステータス';
$a->strings['photo'] = '写真';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s が %2$s の %3$s を %4$s としてタグ付けしました';
$a->strings['Follow Thread'] = 'このスレッドをフォロー';
$a->strings['View Status'] = 'ステータスを見る';
$a->strings['View Profile'] = 'プロフィールを見る';
$a->strings['View Photos'] = '写真を見る';
$a->strings['Network Posts'] = 'ネットワーク投稿';
$a->strings['View Contact'] = 'コンタクトを見る';
$a->strings['Send PM'] = 'PMを送る';
$a->strings['Block'] = 'ブロック';
$a->strings['Ignore'] = '無視';
$a->strings['Languages'] = '言語';
$a->strings['Connect/Follow'] = 'つながる/フォローする';
$a->strings['Nothing new here'] = 'ここに新しいものはありません';
$a->strings['Home'] = 'ホーム';
$a->strings['Clear notifications'] = 'クリア通知';
$a->strings['Logout'] = 'ログアウト';
$a->strings['End this session'] = 'このセッションを終了';
$a->strings['Login'] = 'ログイン';
$a->strings['Sign in'] = 'サインイン';
$a->strings['Profile'] = 'プロフィール';
$a->strings['Your profile page'] = 'あなたのプロフィールページ';
$a->strings['Photos'] = '写真';
$a->strings['Your photos'] = 'あなたの写真';
$a->strings['Calendar'] = 'カレンダー';
$a->strings['Personal notes'] = '個人メモ';
$a->strings['Your personal notes'] = 'あなたの個人的なメモ';
$a->strings['Home Page'] = 'ホームページ';
$a->strings['Register'] = '登録';
$a->strings['Create an account'] = 'アカウントを作成する';
$a->strings['Help'] = 'ヘルプ';
$a->strings['Help and documentation'] = 'ヘルプとドキュメント';
$a->strings['Apps'] = 'アプリ';
$a->strings['Addon applications, utilities, games'] = 'アドオンアプリケーション、ユーティリティ、ゲーム';
$a->strings['Search'] = '検索';
$a->strings['Search site content'] = 'サイトのコンテンツを検索';
$a->strings['Full Text'] = '全文';
$a->strings['Tags'] = 'タグ';
$a->strings['Contacts'] = 'コンタクト';
$a->strings['Community'] = 'コミュニティ';
$a->strings['Conversations on this and other servers'] = 'このサーバーと他のサーバーでの会話';
$a->strings['Directory'] = 'ディレクトリ';
$a->strings['People directory'] = '人々の名簿';
$a->strings['Information'] = '情報';
$a->strings['Information about this friendica instance'] = 'このfriendicaインスタンスに関する情報';
$a->strings['Terms of Service'] = '利用規約';
$a->strings['Terms of Service of this Friendica instance'] = 'このFriendicaインスタンスの利用規約';
$a->strings['Network'] = 'ネットワーク';
$a->strings['Conversations from your friends'] = '友達からの会話';
$a->strings['Your posts and conversations'] = 'あなたの投稿と会話';
$a->strings['Introductions'] = '招待';
$a->strings['Friend Requests'] = '友達リクエスト';
$a->strings['Notifications'] = '通知';
$a->strings['See all notifications'] = 'すべての通知を見る';
$a->strings['Mark as seen'] = '既読にする';
$a->strings['Private mail'] = 'プライベートメール';
$a->strings['Inbox'] = '受信トレイ';
$a->strings['Outbox'] = '送信トレイ';
$a->strings['Manage other pages'] = '他のページを管理する';
$a->strings['Settings'] = '設定';
$a->strings['Account settings'] = 'アカウント設定';
$a->strings['Manage/edit friends and contacts'] = '友達とコンタクトを管理/編集する';
$a->strings['Admin'] = '管理者';
$a->strings['Site setup and configuration'] = 'サイトのセットアップと構成';
$a->strings['Navigation'] = 'ナビゲーション';
$a->strings['Site map'] = 'サイトマップ';
$a->strings['first'] = '最初';
$a->strings['prev'] = '前の';
$a->strings['next'] = '次';
$a->strings['last'] = '最終';
$a->strings['Click to open/close'] = 'クリックして開閉';
$a->strings['$1 wrote:'] = '$1 の投稿：';
$a->strings['Encrypted content'] = '暗号化されたコンテンツ';
$a->strings['Invalid source protocol'] = '無効なソースプロトコル';
$a->strings['Invalid link protocol'] = '無効なリンクプロトコル';
$a->strings['Loading more entries...'] = 'さらにエントリを読み込んでいます...';
$a->strings['The end'] = '終わり';
$a->strings['Follow'] = 'フォロー';
$a->strings['Add New Contact'] = '新しいコンタクトを追加';
$a->strings['Enter address or web location'] = '住所またはウェブの場所を入力してください';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = '例: bob@example.com, http://example.com/barbara';
$a->strings['Connect'] = 'つながる';
$a->strings['%d invitation available'] = [
	0 => '%d通の招待が利用できます',
];
$a->strings['Find People'] = '人を見つけます';
$a->strings['Enter name or interest'] = '名前または興味を入力してください';
$a->strings['Examples: Robert Morgenstein, Fishing'] = '例：Robert Morgenstein、釣り';
$a->strings['Find'] = '見つける';
$a->strings['Friend Suggestions'] = '友達の提案';
$a->strings['Similar Interests'] = '同様の興味';
$a->strings['Random Profile'] = 'ランダムプロフィール';
$a->strings['Invite Friends'] = '友達を招待';
$a->strings['Global Directory'] = 'グローバルディレクトリ';
$a->strings['Local Directory'] = 'ローカルディレクトリ';
$a->strings['Relationships'] = '関係';
$a->strings['All Contacts'] = 'すべてのコンタクト';
$a->strings['All Protocols'] = 'すべてのプロトコル';
$a->strings['Everything'] = 'すべて';
$a->strings['Categories'] = 'カテゴリー';
$a->strings['%d contact in common'] = [
	0 => '共通の %d 件のコンタクト',
];
$a->strings['News'] = 'ニュース';
$a->strings['Export'] = 'エクスポート';
$a->strings['Export calendar as ical'] = 'カレンダーをicalとしてエクスポート';
$a->strings['Export calendar as csv'] = 'カレンダーをcsvとしてエクスポート';
$a->strings['No contacts'] = 'コンタクトなし';
$a->strings['%d Contact'] = [
	0 => '%dコンタクト',
];
$a->strings['View Contacts'] = 'コンタクトを表示';
$a->strings['Remove term'] = '用語を削除';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'トレンドタグ（過去%d時間）',
];
$a->strings['More Trending Tags'] = 'よりトレンドのタグ';
$a->strings['XMPP:'] = 'XMPP：';
$a->strings['Location:'] = 'ロケーション：';
$a->strings['Network:'] = 'ネットワーク：';
$a->strings['Unfollow'] = 'フォロー解除';
$a->strings['Mutuals'] = '相互';
$a->strings['Post to Email'] = 'メールに投稿';
$a->strings['Public'] = '一般公開';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'このコンテンツはすべてのフォロワーに表示され、コミュニティページやリンクを知っている人なら誰でも見ることができます。';
$a->strings['Limited/Private'] = '限定/プライベート';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'このコンテンツは、最初のボックスに記載されたメンバーから、2番目のボックスに記載されている人を除いた範囲に対して表示されます。 一般公開はされません。';
$a->strings['Show to:'] = '限定公開先:';
$a->strings['Except to:'] = 'この連絡先を除く:';
$a->strings['CC: email addresses'] = 'CC：メールアドレス';
$a->strings['Example: bob@example.com, mary@example.com'] = '例：bob @ example.com、mary @ example.com';
$a->strings['Connectors'] = 'コネクター';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'データベース構成ファイル "config/local.config.php" に書き込めませんでした。同封のテキストを使用して、Webサーバーのルートに構成ファイルを作成してください。';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'phpmyadminまたはmysqlを使用して、手動でファイル"database.sql "をインポートする必要がある場合があります。';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'WebサーバーPATHに CLI版のPHPが見つかりませんでした。';
$a->strings['PHP executable path'] = 'PHP実行可能ファイルへのPath';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'php実行可能ファイルへのフルパスを入力します。これを空白のままにしてインストールを続行できます。';
$a->strings['Command line PHP'] = 'コマンドライン, CLI PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'PHP実行可能ファイルはphp cliバイナリではありません（cgi-fgciバージョンである可能性があります）';
$a->strings['Found PHP version: '] = 'PHPバージョンが見つかりました：';
$a->strings['PHP cli binary'] = 'PHP CLIバイナリ';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'ご使用のシステムのコマンドラインバージョンのPHPでは、"register_argc_argv "が有効になっていません。';
$a->strings['This is required for message delivery to work.'] = 'これは、メッセージ配信が機能するために必要です。';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'エラー：このシステムの"openssl_pkey_new "関数は暗号化鍵を生成できません';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Windowsで実行している場合は、「 http://www.php.net/manual/en/openssl.installation.php 」を参照してください。';
$a->strings['Generate encryption keys'] = '暗号化鍵を生成する';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'エラー：Apache webserver mod-rewriteモジュールが必要ですが、インストールされていません。';
$a->strings['Apache mod_rewrite module'] = 'Apache mod_rewriteモジュール';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'エラー：PDOまたはMySQLi PHPモジュールが必要ですが、インストールされていません。';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'エラー：PDO用のMySQLドライバーがインストールされていません。';
$a->strings['PDO or MySQLi PHP module'] = 'PDOまたはMySQLi PHPモジュール';
$a->strings['Error, XML PHP module required but not installed.'] = 'エラー、XML PHPモジュールが必要ですが、インストールされていません。';
$a->strings['XML PHP module'] = 'XML PHPモジュール';
$a->strings['libCurl PHP module'] = 'libCurl PHPモジュール';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'エラー：libCURL PHPモジュールが必要ですが、インストールされていません。';
$a->strings['GD graphics PHP module'] = 'GDグラフィックスPHPモジュール';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'エラー：JPEGサポート付きのGDグラフィックPHPモジュールが必要ですが、インストールされていません。';
$a->strings['OpenSSL PHP module'] = 'OpenSSL PHPモジュール';
$a->strings['Error: openssl PHP module required but not installed.'] = 'エラー：openssl PHPモジュールが必要ですが、インストールされていません。';
$a->strings['mb_string PHP module'] = 'mb_string PHPモジュール';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'エラー：mb_string PHPモジュールが必要ですが、インストールされていません。';
$a->strings['iconv PHP module'] = 'iconv PHPモジュール';
$a->strings['Error: iconv PHP module required but not installed.'] = 'エラー：iconv PHPモジュールが必要ですが、インストールされていません。';
$a->strings['POSIX PHP module'] = 'POSIX PHPモジュール';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'エラー：POSIX PHPモジュールが必要ですが、インストールされていません。';
$a->strings['JSON PHP module'] = 'JSON PHPモジュール';
$a->strings['Error: JSON PHP module required but not installed.'] = 'エラー：JSON PHPモジュールが必要ですが、インストールされていません。';
$a->strings['File Information PHP module'] = 'ファイル情報PHPモジュール';
$a->strings['Error: File Information PHP module required but not installed.'] = 'エラー：ファイル情報PHPモジュールが必要ですが、インストールされていません。';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Webインストーラーは、Webサーバーの"config "フォルダーに"local.config.php "というファイルを作成できる必要がありますが、作成できません。';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'これはほとんどの場合、Webサーバーがフォルダーにファイルを書き込むことができない場合でも、許可設定です。';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'この手順の最後に、Friendica "config "フォルダーのlocal.config.phpという名前のファイルに保存するテキストを提供します。';
$a->strings['config/local.config.php is writable'] = 'config/local.config.php は書き込み可能です';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'FriendicaはSmarty3テンプレートエンジンを使用してWebビューをレンダリングします。 Smarty3はテンプレートをPHPにコンパイルして、レンダリングを高速化します。';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'これらのコンパイル済みテンプレートを保存するには、WebサーバーがFriendica最上位フォルダーの下のディレクトリview / smarty3 /への書き込みアクセス権を持っている必要があります。';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Webサーバーを実行するユーザー（www-dataなど）がこのフォルダーへの書き込みアクセス権を持っていることを確認してください。';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = '注：セキュリティ対策として、Webサーバーにview / smarty3 /のみへの書き込みアクセス権を与える必要があります。含まれるテンプレートファイル（.tpl）ではありません。';
$a->strings['view/smarty3 is writable'] = 'view / smarty3は書き込み可能です';
$a->strings['Error message from Curl when fetching'] = '取得時のCurlからのエラーメッセージ';
$a->strings['Url rewrite is working'] = 'URLの書き換えが機能しています';
$a->strings['ImageMagick PHP extension is not installed'] = 'ImageMagick PHP拡張機能がインストールされていません';
$a->strings['ImageMagick PHP extension is installed'] = 'ImageMagick PHP拡張機能がインストールされています';
$a->strings['Database already in use.'] = 'データベースはすでに使用されています。';
$a->strings['Could not connect to database.'] = 'データベースに接続できません。';
$a->strings['Monday'] = '月曜';
$a->strings['Tuesday'] = '火曜日';
$a->strings['Wednesday'] = '水曜日';
$a->strings['Thursday'] = '木曜日';
$a->strings['Friday'] = '金曜日';
$a->strings['Saturday'] = '土曜日';
$a->strings['Sunday'] = '日曜日';
$a->strings['January'] = '1月';
$a->strings['February'] = '2月';
$a->strings['March'] = '3月';
$a->strings['April'] = '4月';
$a->strings['May'] = '5月';
$a->strings['June'] = '6月';
$a->strings['July'] = '7月';
$a->strings['August'] = '8月';
$a->strings['September'] = '9月';
$a->strings['October'] = '10月';
$a->strings['November'] = '11月';
$a->strings['December'] = '12月';
$a->strings['Mon'] = '月';
$a->strings['Tue'] = '火';
$a->strings['Wed'] = '水';
$a->strings['Thu'] = '木';
$a->strings['Fri'] = '金';
$a->strings['Sat'] = '土';
$a->strings['Sun'] = '日';
$a->strings['Jan'] = '1月';
$a->strings['Feb'] = '2月';
$a->strings['Mar'] = '3月';
$a->strings['Apr'] = '4月';
$a->strings['Jun'] = '6月';
$a->strings['Jul'] = '7月';
$a->strings['Aug'] = '8月';
$a->strings['Sep'] = '9月';
$a->strings['Oct'] = '10月';
$a->strings['Nov'] = '11月';
$a->strings['Dec'] = '12月';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'ログファイル \' %s \' は使用できません。ログ機能が使用できません。(エラー: \' %s \' )';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'デバッグログファイル \' %s \' は使用できません。ログ機能が使用できません。(エラー: \' %s \' )';
$a->strings['Storage base path'] = 'ストレージのbase path';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'アップロードされたファイルが保存されるフォルダです。最大限のセキュリティを確保するために、これはWebサーバーフォルダツリー外のパスである必要があります';
$a->strings['Enter a valid existing folder'] = '有効な既存のフォルダを入力してください';
$a->strings['Update %s failed. See error logs.'] = '%sの更新に失敗しました。エラーログを参照してください。';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				friendicaの開発者は更新 %s をリリースしました。
				しかし、私がそれをインストールしようとしたとき、何かをひどく間違ったようです。
				これはすぐに修正される必要がありますが、私一人では解決できません。
自己解決が無理な場合はfriendica開発者へコンタクトをとってください。データベースが無効である可能性があります。';
$a->strings['[Friendica Notify] Database update'] = '[Friendica Notify]データベースの更新';
$a->strings['
Error %d occurred during database update:
%s
'] = '
データベースの更新中にエラー%dが発生しました：
%s
';
$a->strings['Errors encountered performing database changes: '] = 'データベース変更の実行中に発生したエラー：';
$a->strings['%s: Database update'] = '%s ：データベースの更新';
$a->strings['%s: updating %s table.'] = '%s ： %sテーブルを更新しています。';
$a->strings['Unauthorized'] = '認証されていません';
$a->strings['Internal Server Error'] = '内部サーバーエラー';
$a->strings['Legacy module file not found: %s'] = 'レガシーモジュールファイルが見つかりません： %s';
$a->strings['Everybody'] = 'みんな';
$a->strings['edit'] = '編集する';
$a->strings['add'] = '加える';
$a->strings['Approve'] = '承認する';
$a->strings['Organisation'] = '組織';
$a->strings['Relay'] = '中継';
$a->strings['Disallowed profile URL.'] = '許可されていないプロフィールURL。';
$a->strings['Blocked domain'] = 'ブロックされたドメイン';
$a->strings['Connect URL missing.'] = '接続URLがありません。';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'コンタクトを追加できませんでした。ページの "設定" -> "ソーシャルネットワーク" で、関連するネットワーク認証情報を確認してください。';
$a->strings['The profile address specified does not provide adequate information.'] = '指定されたプロフィールアドレスは、適切な情報を提供しません。';
$a->strings['No compatible communication protocols or feeds were discovered.'] = '互換性のある通信プロトコルまたはフィードは見つかりませんでした。';
$a->strings['An author or name was not found.'] = '著者または名前が見つかりませんでした。';
$a->strings['No browser URL could be matched to this address.'] = 'このアドレスに一致するブラウザURLはありません。';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = '@スタイルのIDアドレスを既知のプロトコルまたは電子メールのコンタクトと一致させることができません。';
$a->strings['Use mailto: in front of address to force email check.'] = 'メールチェックを強制するには、アドレスの前にmailto：を使用します。';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = '限定公開のプロフィールです。この人はあなたから直接/個人的な通知を受け取ることができません。';
$a->strings['Unable to retrieve contact information.'] = 'コンタクト情報を取得できません。';
$a->strings['Starts:'] = '開始：';
$a->strings['Finishes:'] = '終了：';
$a->strings['all-day'] = '一日中';
$a->strings['Sept'] = '9月';
$a->strings['today'] = '今日';
$a->strings['month'] = '月';
$a->strings['week'] = '週間';
$a->strings['day'] = '日';
$a->strings['No events to display'] = '表示するイベントはありません';
$a->strings['Access to this profile has been restricted.'] = 'このプロフィールへのアクセスは制限されています。';
$a->strings['l, F j'] = 'l, F j';
$a->strings['Edit event'] = 'イベントを編集';
$a->strings['Duplicate event'] = '重複イベント';
$a->strings['Delete event'] = 'イベントを削除';
$a->strings['l F d, Y \@ g:i A'] = 'l F d, Y \@ g:i A';
$a->strings['D g:i A'] = 'D g:i A';
$a->strings['g:i A'] = 'g:i A';
$a->strings['Show map'] = '地図を表示';
$a->strings['Hide map'] = '地図を隠す';
$a->strings['%s\'s birthday'] = '%sの誕生日';
$a->strings['Happy Birthday %s'] = 'ハッピーバースデー %s';
$a->strings['activity'] = 'アクティビティ';
$a->strings['post'] = '投稿';
$a->strings['bytes'] = 'バイト';
$a->strings['View on separate page'] = '個別のページで見る';
$a->strings['[no subject]'] = '[件名なし]';
$a->strings['Wall Photos'] = 'ウォール写真';
$a->strings['Edit profile'] = 'プロフィール編集';
$a->strings['Change profile photo'] = 'プロフィール写真を変更';
$a->strings['Homepage:'] = 'ホームページ：';
$a->strings['About:'] = 'この場所について:';
$a->strings['Atom feed'] = 'Atomフィード';
$a->strings['F d'] = 'F d';
$a->strings['[today]'] = '[今日]';
$a->strings['Birthday Reminders'] = '誕生日のリマインダー';
$a->strings['Birthdays this week:'] = '今週の誕生日：';
$a->strings['g A l F d'] = 'g A l F d';
$a->strings['[No description]'] = '[説明なし]';
$a->strings['Event Reminders'] = 'イベントリマインダー';
$a->strings['Upcoming events the next 7 days:'] = '今後7日間の今後のイベント：';
$a->strings['Hometown:'] = '出身地：';
$a->strings['Sexual Preference:'] = '性的嗜好：';
$a->strings['Political Views:'] = '政見：';
$a->strings['Religious Views:'] = '宗教的見解：';
$a->strings['Likes:'] = '好きなもの：';
$a->strings['Dislikes:'] = '嫌いなもの：';
$a->strings['Title/Description:'] = 'タイトル説明：';
$a->strings['Summary'] = '概要';
$a->strings['Musical interests'] = '音楽的興味';
$a->strings['Books, literature'] = '本、文学';
$a->strings['Television'] = 'テレビ';
$a->strings['Film/dance/culture/entertainment'] = '映画/ダンス/文化/エンターテイメント';
$a->strings['Hobbies/Interests'] = '趣味/興味';
$a->strings['Love/romance'] = '愛/ロマンス';
$a->strings['Work/employment'] = '仕事/雇用';
$a->strings['School/education'] = '学校教育';
$a->strings['Contact information and Social Networks'] = 'コンタクト情報とソーシャルネットワーク';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = '重大なエラー：セキュリティキーの生成に失敗しました。';
$a->strings['Login failed'] = 'ログインに失敗しました';
$a->strings['Not enough information to authenticate'] = '認証に十分な情報がありません';
$a->strings['Password can\'t be empty'] = 'パスワードは空にできません';
$a->strings['Empty passwords are not allowed.'] = '空のパスワードは許可されていません。';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = '新しいパスワードは公開データダンプで公開されています。別のパスワードを選択してください。';
$a->strings['Passwords do not match. Password unchanged.'] = 'パスワードが一致していません。パスワードは変更されていません。';
$a->strings['An invitation is required.'] = '招待状が必要です。';
$a->strings['Invitation could not be verified.'] = '招待を確認できませんでした。';
$a->strings['Invalid OpenID url'] = '無効なOpenID URL';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = '指定したOpenIDでログイン中に問題が発生しました。 IDの正しいスペルを確認してください。';
$a->strings['The error message was:'] = 'エラーメッセージは次のとおりです。';
$a->strings['Please enter the required information.'] = '必要な情報を入力してください。';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length（ %s ）とsystem.username_max_length（ %s ）は、お互いを除外し、値を交換しています。';
$a->strings['Username should be at least %s character.'] = [
	0 => 'ユーザー名は少なくとも%s文字である必要があります。',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'ユーザー名は最大で%s文字にする必要があります。',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'それはあなたのフルネーム（ファースト/ラスト）ではないようです。';
$a->strings['Your email domain is not among those allowed on this site.'] = 'あなたのメールドメインは、このサイトで許可されているものではありません。';
$a->strings['Not a valid email address.'] = '有効な電子メールアドレスではありません。';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'そのニックネームは、ノード管理者によって登録がブロックされました。';
$a->strings['Cannot use that email.'] = 'そのメールは使用できません。';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'ニックネームにはa-z、0-9、および _ のみを含めることができます。';
$a->strings['Nickname is already registered. Please choose another.'] = 'ニックネームはすでに登録されています。別のものを選択してください。';
$a->strings['An error occurred during registration. Please try again.'] = '登録中にエラーが発生しました。もう一度試してください。';
$a->strings['An error occurred creating your default profile. Please try again.'] = '既定のプロフィールの作成中にエラーが発生しました。もう一度試してください。';
$a->strings['An error occurred creating your self contact. Please try again.'] = '自己コンタクトの作成中にエラーが発生しました。もう一度試してください。';
$a->strings['Friends'] = '友だち';
$a->strings['Profile Photos'] = 'プロフィール写真';
$a->strings['Registration details for %s'] = '%s の登録の詳細';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			%1$s さん、
				%2$s に登録していただきありがとうございます。アカウントは管理者による承認待ちです。

			ログインの詳細は次のとおりです。

			サイトの場所：	%3$s
			ログイン名：		%4$s
			パスワード：		%5$s
		';
$a->strings['Registration at %s'] = '%s登録';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
			%1$sさん、
				%2$sで登録していただきありがとうございます。アカウントが作成されました。
		';
$a->strings['Addon not found.'] = 'アドオンが見つかりません。';
$a->strings['Addon %s disabled.'] = 'アドオン%sを無効にしました。';
$a->strings['Addon %s enabled.'] = 'アドオン%sが有効になりました。';
$a->strings['Disable'] = '無効にする';
$a->strings['Enable'] = '有効にする';
$a->strings['Administration'] = '運営管理';
$a->strings['Addons'] = 'アドオン';
$a->strings['Toggle'] = 'トグル';
$a->strings['Author: '] = '著者：';
$a->strings['Maintainer: '] = 'メンテナー：';
$a->strings['Addon %s failed to install.'] = 'アドオン %s のインストールに失敗しました。';
$a->strings['Save Settings'] = '設定を保存';
$a->strings['Reload active addons'] = 'アクティブなアドオンをリロードする';
$a->strings['Update has been marked successful'] = '更新は正常にマークされました';
$a->strings['Database structure update %s was successfully applied.'] = 'データベース構造の更新 %s が正常に適用されました。';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'データベース構造の更新 %s は次のエラーで失敗しました： %s';
$a->strings['Executing %s failed with error: %s'] = '次のエラーで %s の実行に失敗しました： %s';
$a->strings['Update %s was successfully applied.'] = '更新 %s が正常に適用されました。';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = '更新 %s はステータスを返しませんでした。成功した場合は不明です。';
$a->strings['There was no additional update function %s that needed to be called.'] = '呼び出される必要のある機能 %s について追加の更新はありませんでした。';
$a->strings['No failed updates.'] = '失敗した更新はありません。';
$a->strings['Check database structure'] = 'データベース構造を確認する';
$a->strings['Failed Updates'] = '失敗した更新';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'これには、ステータスを返さなかった1139より前の更新は含まれません。';
$a->strings['Mark success (if update was manually applied)'] = '成功をマークする（更新が手動で適用された場合）';
$a->strings['Attempt to execute this update step automatically'] = 'この更新手順を自動的に実行しようとします';
$a->strings['No'] = 'いいえ';
$a->strings['Yes'] = 'はい';
$a->strings['Manage Additional Features'] = '追加機能を管理する';
$a->strings['Other'] = 'その他';
$a->strings['unknown'] = '未知の';
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'このページでは、Friendicaノードが属するフェデレーションソーシャルネットワークの既知の部分について統計を提供します。これらの数値は完全なものではなく、ノードが認識しているネットワークの部分のみを反映しています。';
$a->strings['Federation Statistics'] = 'フェデレーション統計';
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'ログファイル \' %s \' は書き込みできません。ログ機能が使用できません。';
$a->strings['PHP log currently enabled.'] = '現在有効なPHPログ。';
$a->strings['PHP log currently disabled.'] = 'PHPログは現在無効になっています。';
$a->strings['Logs'] = 'ログ';
$a->strings['Clear'] = 'クリア';
$a->strings['Enable Debugging'] = 'デバッグを有効にする';
$a->strings['Log file'] = 'ログファイル';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Webサーバーから書き込み可能である必要があります。 Friendicaの最上位ディレクトリからの相対パス。';
$a->strings['Log level'] = 'ログレベル';
$a->strings['PHP logging'] = 'PHPロギング';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'PHPのエラーと警告のログを一時的に有効にするには、インストールのindex.phpファイルに次を追加します。 「error_log」行に設定されたファイル名は、Friendicaの最上位ディレクトリに関連しており、Webサーバーが書き込み可能である必要があります。 「log_errors」および「display_errors」のオプション「1」はこれらのオプションを有効にすることであり、「0」に設定すると無効になります。';
$a->strings['View Logs'] = 'ログを見る';
$a->strings['Show all'] = 'すべて表示する';
$a->strings['Event details'] = 'イベントの詳細';
$a->strings['Inspect Deferred Worker Queue'] = '遅延ワーカーキューの詳細を見る';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'このページには、遅延ワーカージョブが一覧表示されます。これは、投入時に実行できなかったジョブです。';
$a->strings['Inspect Worker Queue'] = 'ワーカーキューの詳細を見る';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'このページには、現在キューに入れられているワーカージョブが一覧表示されます。これらのジョブは、インストール中に設定したワーカーcronジョブによって処理されます。';
$a->strings['ID'] = 'ID';
$a->strings['Job Parameters'] = 'ジョブパラメータ';
$a->strings['Created'] = '作成した';
$a->strings['Priority'] = '優先度';
$a->strings['No special theme for mobile devices'] = 'モバイルデバイス向けの特別なテーマはありません';
$a->strings['%s - (Experimental)'] = '%s （実験的）';
$a->strings['No community page'] = 'コミュニティページなし';
$a->strings['Public postings from users of this site'] = 'このサイトのユーザーからの一般公開投稿';
$a->strings['Public postings from the federated network'] = 'フェデレーションネットワークからの一般公開投稿';
$a->strings['Public postings from local users and the federated network'] = 'ローカルユーザーとフェデレーションネットワークからの一般公開投稿';
$a->strings['Multi user instance'] = 'マルチユーザーインスタンス';
$a->strings['Closed'] = '閉まっている';
$a->strings['Requires approval'] = '承認が必要';
$a->strings['Open'] = '開いた';
$a->strings['Don\'t check'] = 'チェックしない';
$a->strings['check the stable version'] = '安定版を確認してください';
$a->strings['check the development version'] = '開発バージョンを確認する';
$a->strings['Site'] = 'サイト';
$a->strings['Republish users to directory'] = 'ユーザーをディレクトリに再公開する';
$a->strings['Registration'] = '登録';
$a->strings['File upload'] = 'ファイルのアップロード';
$a->strings['Policies'] = 'ポリシー';
$a->strings['Advanced'] = '詳細';
$a->strings['Auto Discovered Contact Directory'] = '自動検出されたコンタクトディレクトリ';
$a->strings['Performance'] = '性能';
$a->strings['Worker'] = 'ワーカー';
$a->strings['Message Relay'] = 'メッセージ中継';
$a->strings['Site name'] = 'サイト名';
$a->strings['Sender Email'] = '送信者のメール';
$a->strings['The email address your server shall use to send notification emails from.'] = 'サーバーが通知メールの送信に使用するメールアドレス。';
$a->strings['Banner/Logo'] = 'バナー/ロゴ';
$a->strings['Shortcut icon'] = 'ショートカットアイコン';
$a->strings['Link to an icon that will be used for browsers.'] = 'ブラウザーに使用されるアイコンへのリンク。';
$a->strings['Touch icon'] = 'タッチアイコン';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'タブレットやモバイルで使用されるアイコンへのリンク。';
$a->strings['Additional Info'] = '追加情報';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'パブリックサーバーの場合：追加の情報をここに追加して、 %s/servers にリストできます。';
$a->strings['System language'] = 'システム言語';
$a->strings['System theme'] = 'システムテーマ';
$a->strings['Mobile system theme'] = 'モバイルシステムのテーマ';
$a->strings['Theme for mobile devices'] = 'モバイルデバイスのテーマ';
$a->strings['Force SSL'] = 'SSLを強制する';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'すべての非SSL要求をSSLに強制する-注意：一部のシステムでは、無限ループにつながる可能性があります。';
$a->strings['Single user instance'] = 'シングルユーザーインスタンス';
$a->strings['Make this instance multi-user or single-user for the named user'] = '指定されたユーザーに対してこのインスタンスをマルチユーザーまたはシングルユーザーにします';
$a->strings['Maximum image size'] = '最大画像サイズ';
$a->strings['Maximum image length'] = '最大画像長';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'アップロードされた画像の最長辺のピクセル単位の最大長。デフォルトは-1で、制限がないことを意味します。';
$a->strings['JPEG image quality'] = 'JPEG画像品質';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'アップロードされたJPEGSは、この品質設定[0-100]で保存されます。デフォルトは100で、完全な品質です。';
$a->strings['Register policy'] = '登録ポリシー';
$a->strings['Maximum Daily Registrations'] = '毎日の最大登録数';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = '上記で登録が許可されている場合、これは1日に受け入れる新しいユーザー登録の最大数を設定します。レジスタがクローズに設定されている場合、この設定は効果がありません。';
$a->strings['Register text'] = '登録テキスト';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = '登録ページに目立つように表示されます。ここでBBCodeを使用できます。';
$a->strings['Forbidden Nicknames'] = '禁止されたニックネーム';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = '登録が禁止されているニックネームのカンマ区切りリスト。プリセットは、RFC 2142に基づくロール名のリストです。';
$a->strings['Accounts abandoned after x days'] = 'x日の間 放置されたアカウント';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = '放置アカウントの外部サイトについてポーリングを停止しシステムリソースを節約します。時間制限なしの場合は0を入力します。';
$a->strings['Allowed friend domains'] = '許可された友達ドメイン';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'このサイトとの友達関係を確立できるドメインのカンマ区切りリスト。ワイルドカードが使用できます。すべてのドメインを許可するには空白にしてください。';
$a->strings['Allowed email domains'] = '許可されたメールドメイン';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'このサイトへの登録用の電子メールアドレスで許可されるドメインのカンマ区切りリスト。ワイルドカードが使用できます。すべてのドメインを許可するには空白にしてください。';
$a->strings['No OEmbed rich content'] = 'OEmbed リッチコンテンツなし';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = '以下にリストされているドメインを除き、リッチコンテンツ（埋め込みPDFなど）を表示しないでください。';
$a->strings['Block public'] = '一般公開をブロック';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'このサイトの一般公開済み個人ページを除き、すべてのページで非ログイン状態のアクセスをブロックするには、ここをチェックします。';
$a->strings['Force publish'] = '公開を強制する';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'このサイトのすべてのプロフィールがサイトディレクトリにリストされるように強制するには、チェックします。';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'これを有効にすると、GDPRなどのプライバシー法に違反する可能性があります。';
$a->strings['Global directory URL'] = 'グローバルディレクトリURL';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'グローバルディレクトリへのURL。これが設定されていない場合、グローバルディレクトリはアプリケーションで全く利用できなくなります。';
$a->strings['Private posts by default for new users'] = '新規ユーザー向けの 既定のプライベート投稿';
$a->strings['Don\'t include post content in email notifications'] = 'メール通知に投稿本文を含めないでください';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'プライバシー対策として、このサイトから送信されるメール通知に投稿/コメント/プライベートメッセージなどのコンテンツを含めないでください。';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'アプリメニューにリストされているアドオンへの公開アクセスを許可しません。';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'このチェックボックスをオンにすると、アプリメニューにリストされているアドオンがメンバーのみに制限されます。';
$a->strings['Don\'t embed private images in posts'] = '投稿にプライベート画像を埋め込まないでください';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = '投稿内のローカルでホストされているプライベート写真を画像の埋め込みコピーで置き換えないでください。つまり、プライベート写真を含む投稿を受け取ったコンタクトは、各画像を認証して読み込む必要があり、時間がかかる場合があります。';
$a->strings['Explicit Content'] = '明示的なコンテンツ';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'これを設定して、このノードが主に未成年者には適さない可能性のある露骨なコンテンツを目的とすることを通知します。この情報はノード情報で公開され、たとえばグローバルディレクトリによって使用され、参加するノードのリストからノードをフィルタリングします。さらに、これに関するメモがユーザー登録ページに表示されます。';
$a->strings['Allow Users to set remote_self'] = 'ユーザーがremote_selfを設定できるようにする';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'これをチェックすると、すべてのユーザーがコンタクトの修復ダイアログですべてのコンタクトをremote_selfとしてマークできます。コンタクトにこのフラグを設定すると、ユーザーストリームでそのコンタクトのすべての投稿がミラーリングされます。';
$a->strings['Community pages for visitors'] = '訪問者向けのコミュニティページ';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = '訪問者が利用できるコミュニティページ。ローカルユーザーには常に両方のページが表示されます。';
$a->strings['Posts per user on community page'] = 'コミュニティページのユーザーごとの投稿';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Friendicaがサブディレクトリにインストールされたため、Diasporaサポートを有効にできません。';
$a->strings['Enable Diaspora support'] = 'Diasporaサポートを有効にする';
$a->strings['Verify SSL'] = 'SSLを検証する';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = '必要に応じて、厳密な証明書チェックをオンにすることができます。これは、自己署名SSLサイトに（まったく）接続できないことを意味します。';
$a->strings['Proxy user'] = 'プロキシユーザー';
$a->strings['Proxy URL'] = 'プロキシURL';
$a->strings['Network timeout'] = 'ネットワークタイムアウト';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = '値は秒単位です。無制限の場合は0に設定します（推奨されません）。';
$a->strings['Maximum Load Average'] = '最大負荷平均';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'このシステム 負荷/Load を超えると、配信・ポーリングプロセスの実行は延期されます。 - 既定の値は%dです。';
$a->strings['Minimal Memory'] = '最小限のメモリ';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'ワーカーの最小空きメモリ（MB）。 / proc / meminfoへのアクセスが必要-デフォルトは0（無効）。';
$a->strings['Discover contacts from other servers'] = '他のサーバーからコンタクトを発見する';
$a->strings['Days between requery'] = '再クエリの間隔';
$a->strings['Search the local directory'] = 'ローカルディレクトリを検索する';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'グローバルディレクトリではなくローカルディレクトリを検索します。ローカルで検索する場合、すべての検索はバックグラウンドでグローバルディレクトリで実行されます。これにより、同じ検索を繰り返した場合の検索結果が改善されます。';
$a->strings['Publish server information'] = 'サーバー情報を公開する';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = '有効にすると、一般的なサーバーと使用状況データが公開されます。データには、サーバーの名前とバージョン、パブリックプロフィールを持つユーザーの数、投稿の数、およびアクティブ化されたプロトコルとコネクタが含まれます。詳細については、<a href="http://the-federation.info/"> the-federation.info </a>をご覧ください。';
$a->strings['Check upstream version'] = 'アップストリームバージョンを確認する';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'githubで新しいFriendicaバージョンのチェックを有効にします。新しいバージョンがある場合は、管理パネルの概要で通知されます。';
$a->strings['Suppress Tags'] = 'タグを非表示';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = '投稿の最後にハッシュタグのリストを表示しないようにします。';
$a->strings['Clean database'] = 'データベースを消去';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = '古いリモート項目、孤立したデータベースレコード、および古いコンテンツを他のヘルパーテーブルから削除します。';
$a->strings['Lifespan of remote items'] = 'リモート項目の寿命';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'データベースのクリーンアップが有効な場合、これはリモート項目が削除されるまでの日数を定義します。自身の項目、およびマークまたはファイルされた項目は常に保持されます。 0はこの動作を無効にします。';
$a->strings['Lifespan of unclaimed items'] = '請求されていない項目の寿命';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'データベースのクリーンアップが有効になっている場合、これは、要求されていないリモート項目（主に中継からのコンテンツ）が削除されるまでの日数を定義します。デフォルト値は90日です。 0に設定されている場合、リモート項目の一般的なライフスパン値がデフォルトになります。';
$a->strings['Lifespan of raw conversation data'] = 'Raw会話データの寿命';
$a->strings['Maximum numbers of comments per post'] = '投稿あたりのコメントの最大数';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = '各投稿に表示されるコメントの数は？デフォルト値は100です。';
$a->strings['Temp path'] = '一時パス';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Webサーバーがシステムの一時パスにアクセスできない制限されたシステムがある場合は、ここに別のパスを入力します。';
$a->strings['Only search in tags'] = 'タグでのみ検索';
$a->strings['On large systems the text search can slow down the system extremely.'] = '大規模なシステムでは、テキスト検索によりシステムの速度が著しく低下する可能性があります。';
$a->strings['Maximum number of parallel workers'] = '並列ワーカーの最大数';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = '共有ホスティング事業者では、これを%dに設定します。大規模なシステムでは、 %dの値は素晴らしいでしょう。既定の値は%dです。';
$a->strings['Enable fastlane'] = 'fastlaneを有効にする';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = '有効にすると、優先度の高いプロセスが優先度の低いプロセスによってブロックされた場合、fastlaneメカニズムは追加のワーカーを開始します。';
$a->strings['Direct relay transfer'] = '直接中継転送';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = '中継サーバーを使用せずに他のサーバーに直接転送できるようにします';
$a->strings['Relay scope'] = '中継スコープ';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = '"all "または"tags "にすることができます。 「すべて」は、すべての一般公開投稿を受信することを意味します。 「タグ」は、選択したタグのある投稿のみを受信することを意味します。';
$a->strings['Disabled'] = '無効';
$a->strings['all'] = 'すべて';
$a->strings['tags'] = 'タグ';
$a->strings['Server tags'] = 'サーバータグ';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = '"tags "サブスクリプションのタグのコンマ区切りリスト。';
$a->strings['Allow user tags'] = 'ユーザータグを許可する';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = '有効にすると、保存された検索のタグが、"relay_server_tags "に加えて"tags "サブスクリプションに使用されます。';
$a->strings['Start Relocation'] = '再配置を開始';
$a->strings['Invalid storage backend setting value.'] = '無効なストレージバックエンド設定値です。';
$a->strings['Database (legacy)'] = 'データベース（レガシー）';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'DBは引き続きMyISAMテーブルで実行されます。エンジンタイプをInnoDBに変更する必要があります。 Friendicaは今後InnoDBのみの機能を使用するため、これを変更する必要があります。テーブルエンジンの変換に役立つガイドについては、<a href="%s">こちら</a>をご覧ください。 Friendicaインストールの<tt> php bin/console.php dbstructure toinnodb </tt>コマンドを使用して自動変換することもできます。<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'ダウンロード可能なFriendicaの新しいバージョンがあります。現在のバージョンは%1$s 、アップストリームバージョンは%2$sです。';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'データベースの更新に失敗しました。コマンドラインから「php bin/console.php dbstructure update」を実行し、表示される可能性のあるエラーを確認してください。';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = '最後の更新に失敗しました。コマンドラインから「php bin/console.php dbstructure update」を実行し、表示される可能性のあるエラーを確認してください。 （エラーの一部は、おそらくログファイル内にあります。）';
$a->strings['The worker was never executed. Please check your database structure!'] = 'ワーカーは実行されませんでした。データベース構造を確認してください！';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = '最後のワーカー実行は%s UTCでした。これは1時間以上前です。 crontabの設定を確認してください。';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Friendicaの設定はconfig/local.config.phpに保存されるようになりました。config/local-sample.config.phpをコピーし、設定を<code> .htconfig.php </code>から移動してください。移行のヘルプについては、<a href="%s"> Configヘルプページ</a>をご覧ください。';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Friendicaの設定はconfig/local.config.phpに保存されるようになりました。config/ local-sample.config.phpをコピーして、設定を<code> config / local.ini.php </code>から移動してください。移行のヘルプについては、<a href="%s"> Configヘルプページ</a>をご覧ください。';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = 'システムで<a href="%s"> %s </a>に到達できません。これは、サーバー間の通信を妨げる重大な構成の問題です。ヘルプについては、<a href="%s">インストールページ</a>をご覧ください。';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'Friendicaのsystem.basepathは \'%s\' から \'%s\' に更新されました。差異を避けるために、データベースからsystem.basepathを削除してください。';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'Friendicaの現在のsystem.basepath \'%s\' は間違っています。構成ファイル \'%s\'は使用されていません。';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'Friendicaの現在のsystem.basepath \'%s\'は、構成ファイル \'%s\'と等しくありません。設定を修正してください。';
$a->strings['Message queues'] = 'メッセージキュー';
$a->strings['Server Settings'] = 'サーバー設定';
$a->strings['Version'] = 'バージョン';
$a->strings['Active addons'] = 'アクティブなアドオン';
$a->strings['Theme %s disabled.'] = 'テーマ%sを無効にしました。';
$a->strings['Theme %s successfully enabled.'] = 'テーマ%sが有効になりました。';
$a->strings['Theme %s failed to install.'] = 'テーマ%sのインストールに失敗しました。';
$a->strings['Screenshot'] = 'スクリーンショット';
$a->strings['Themes'] = 'テーマ';
$a->strings['Unknown theme.'] = '不明なテーマ。';
$a->strings['Reload active themes'] = 'アクティブなテーマをリロードする';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'システムにテーマが見つかりません。 %1$sに配置する必要があります';
$a->strings['[Experimental]'] = '[実験的]';
$a->strings['[Unsupported]'] = '[サポートされていません]';
$a->strings['Display Terms of Service'] = '利用規約を表示する';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = '利用規約ページを有効にします。これを有効にすると、登録フォームと一般情報ページに規約へのリンクが追加されます。';
$a->strings['Display Privacy Statement'] = 'プライバシーに関する声明を表示する';
$a->strings['Privacy Statement Preview'] = 'プライバシーに関する声明のプレビュー';
$a->strings['The Terms of Service'] = '利用規約';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'ここにノードの利用規約を入力します。 BBCodeを使用できます。セクションのヘッダーは[h2]以下である必要があります。';
$a->strings['Contact not found'] = 'コンタクトが見つかりません';
$a->strings['No installed applications.'] = 'アプリケーションがインストールされていません。';
$a->strings['Applications'] = 'アプリケーション';
$a->strings['Item was not found.'] = '項目が見つかりませんでした。';
$a->strings['Please login to continue.'] = 'この先に進むにはログインしてください。';
$a->strings['Overview'] = '概要';
$a->strings['Configuration'] = '構成';
$a->strings['Additional features'] = '追加機能';
$a->strings['Database'] = 'データベース';
$a->strings['DB updates'] = 'DBの更新';
$a->strings['Inspect Deferred Workers'] = '非同期実行ワーカーの検査';
$a->strings['Inspect worker Queue'] = 'ワーカーキューの検査';
$a->strings['Diagnostics'] = '診断';
$a->strings['PHP Info'] = 'PHP情報';
$a->strings['probe address'] = 'プローブアドレス';
$a->strings['check webfinger'] = 'webfingerで診断';
$a->strings['Babel'] = 'Babel';
$a->strings['Addon Features'] = 'アドオン機能';
$a->strings['User registrations waiting for confirmation'] = '確認待ちのユーザー登録';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => '一日の最大投稿数 %d 件を超えたため、投稿できませんでした。',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => '一週間の最大投稿数 %d 件を超えたため、投稿できませんでした。',
];
$a->strings['Users'] = 'ユーザー';
$a->strings['Tools'] = 'ツール';
$a->strings['Contact Blocklist'] = 'コンタクトブロックリスト';
$a->strings['Server Blocklist'] = 'サーバーブロックリスト';
$a->strings['Delete Item'] = '項目を削除';
$a->strings['Item Source'] = '項目ソース';
$a->strings['Profile Details'] = 'プロフィールの詳細';
$a->strings['Only You Can See This'] = 'これしか見えない';
$a->strings['Tips for New Members'] = '新会員のためのヒント';
$a->strings['More'] = '更に';
$a->strings['People Search - %s'] = '人を検索- %s';
$a->strings['No matches'] = '一致する項目がありません';
$a->strings['Account'] = 'アカウント';
$a->strings['Two-factor authentication'] = '二要素認証';
$a->strings['Display'] = '表示';
$a->strings['Social Networks'] = 'ソーシャルネットワーク';
$a->strings['Manage Accounts'] = 'アカウントの管理';
$a->strings['Connected apps'] = '接続されたアプリ';
$a->strings['Import Contacts'] = 'コンタクトをインポートする';
$a->strings['Export personal data'] = '個人データのエクスポート';
$a->strings['Remove account'] = 'アカウントを削除';
$a->strings['This page is missing a url parameter.'] = 'このページにはurlパラメーターがありません。';
$a->strings['The post was created'] = '投稿が作成されました';
$a->strings['Failed to remove event'] = 'イベントを削除できませんでした';
$a->strings['Event can not end before it has started.'] = 'イベントは開始する前に終了できません。';
$a->strings['Event title and start time are required.'] = 'イベントのタイトルと開始時間が必要です。';
$a->strings['Starting date and Title are required.'] = '開始日とタイトルが必要です。';
$a->strings['Event Starts:'] = 'イベント開始：';
$a->strings['Required'] = '必須';
$a->strings['Finish date/time is not known or not relevant'] = '終了日時が不明であるか、関連性がない';
$a->strings['Event Finishes:'] = 'イベント終了：';
$a->strings['Share this event'] = 'このイベントを共有する';
$a->strings['Basic'] = 'ベーシック';
$a->strings['This calendar format is not supported'] = 'このカレンダー形式はサポートされていません';
$a->strings['No exportable data found'] = 'エクスポート可能なデータが見つかりません';
$a->strings['calendar'] = 'カレンダー';
$a->strings['Events'] = 'イベント';
$a->strings['View'] = '表示する';
$a->strings['Create New Event'] = '新しいイベントを作成';
$a->strings['list'] = 'リスト';
$a->strings['Contact not found.'] = 'コンタクトが見つかりません。';
$a->strings['Invalid contact.'] = '無効なコンタクト。';
$a->strings['Contact is deleted.'] = 'コンタクトが削除されます。';
$a->strings['Bad request.'] = '要求の形式が正しくありません。';
$a->strings['Filter'] = 'フィルタ';
$a->strings['Members'] = '会員';
$a->strings['Click on a contact to add or remove.'] = 'コンタクトをクリックして追加・削除';
$a->strings['%d contact edited.'] = [
	0 => '%dコンタクトを編集しました。',
];
$a->strings['Show all contacts'] = 'すべてのコンタクトを表示';
$a->strings['Pending'] = '保留';
$a->strings['Only show pending contacts'] = '保留中のコンタクトのみを表示';
$a->strings['Blocked'] = 'ブロックされました';
$a->strings['Only show blocked contacts'] = 'ブロックされたコンタクトのみを表示';
$a->strings['Ignored'] = '無視された';
$a->strings['Only show ignored contacts'] = '無視されたコンタクトのみを表示';
$a->strings['Archived'] = 'アーカイブ済み';
$a->strings['Only show archived contacts'] = 'アーカイブされたコンタクトのみを表示';
$a->strings['Hidden'] = '非表示';
$a->strings['Only show hidden contacts'] = '非表示のコンタクトのみを表示';
$a->strings['Search your contacts'] = 'コンタクトを検索する';
$a->strings['Results for: %s'] = '結果： %s';
$a->strings['Update'] = '更新';
$a->strings['Unblock'] = 'ブロック解除';
$a->strings['Unignore'] = '無視しない';
$a->strings['Batch Actions'] = 'バッチアクション';
$a->strings['Conversations started by this contact'] = 'このコンタクトが開始した会話';
$a->strings['Posts and Comments'] = '投稿とコメント';
$a->strings['Advanced Contact Settings'] = '高度なコンタクト設定';
$a->strings['Mutual Friendship'] = '相互フォロー';
$a->strings['is a fan of yours'] = 'あなたのファンです';
$a->strings['you are a fan of'] = 'あなたはファンです';
$a->strings['Pending outgoing contact request'] = '保留中の送信済みコンタクトリクエスト';
$a->strings['Pending incoming contact request'] = '保留中の受信済みコンタクトリクエスト';
$a->strings['Visit %s\'s profile [%s]'] = '%sのプロフィール[ %s ]を開く';
$a->strings['Contact update failed.'] = 'コンタクトの更新に失敗しました。';
$a->strings['Return to contact editor'] = 'コンタクトエディターに戻る';
$a->strings['Name'] = '名';
$a->strings['Account Nickname'] = 'アカウントのニックネーム';
$a->strings['Account URL'] = 'アカウントURL';
$a->strings['Poll/Feed URL'] = 'ポーリング/フィードURL';
$a->strings['New photo from this URL'] = 'このURLからの新しい写真';
$a->strings['Follower (%s)'] = [
	0 => 'フォロワー（ %s ）',
];
$a->strings['Following (%s)'] = [
	0 => 'フォロー中（ %s ）',
];
$a->strings['Mutual friend (%s)'] = [
	0 => '相互の友人（ %s ）',
];
$a->strings['Contact (%s)'] = [
	0 => 'コンタクト（ %s ）',
];
$a->strings['Access denied.'] = 'アクセスが拒否されました。';
$a->strings['Submit Request'] = 'リクエストを送る';
$a->strings['You already added this contact.'] = 'このコンタクトは既に追加されています。';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'ネットワークの種類を検出できませんでした。コンタクトを追加できません。';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Diasporaのサポートは有効になっていません。コンタクトを追加できません。';
$a->strings['Please answer the following:'] = '以下に答えてください。';
$a->strings['Your Identity Address:'] = 'あなたのIdentityアドレス:';
$a->strings['Profile URL'] = 'プロフィールURL';
$a->strings['Tags:'] = 'タグ：';
$a->strings['%s knows you'] = '%sはあなたを知っています';
$a->strings['Add a personal note:'] = '個人メモを追加します。';
$a->strings['The contact could not be added.'] = 'コンタクトを追加できませんでした。';
$a->strings['Invalid request.'] = '無効なリクエストです。';
$a->strings['No keywords to match. Please add keywords to your profile.'] = '合致するキーワードが有りません。あなたのプロフィールにキーワードを追加してください。';
$a->strings['Profile Match'] = '一致するプロフィール';
$a->strings['Failed to update contact record.'] = 'コンタクトレコードを更新できませんでした。';
$a->strings['Contact has been unblocked'] = 'コンタクトのブロックが解除されました';
$a->strings['Contact has been blocked'] = 'コンタクトがブロックされました';
$a->strings['Contact has been unignored'] = 'コンタクトは無視されていません';
$a->strings['Contact has been ignored'] = 'コンタクトは無視されました';
$a->strings['You are mutual friends with %s'] = 'あなたは%sと共通の友達です';
$a->strings['You are sharing with %s'] = '%sと共有しています';
$a->strings['%s is sharing with you'] = '%sはあなたと共有しています';
$a->strings['Private communications are not available for this contact.'] = 'このコンタクトへのプライベート通信は利用できません。';
$a->strings['Never'] = '全くない';
$a->strings['(Update was not successful)'] = '（更新は成功しませんでした）';
$a->strings['(Update was successful)'] = '（更新は成功しました）';
$a->strings['Suggest friends'] = '友人のおすすめ';
$a->strings['Network type: %s'] = 'ネットワークの種類： %s';
$a->strings['Communications lost with this contact!'] = 'このコンタクトとの通信が失われました！';
$a->strings['Fetch further information for feeds'] = 'フィードの詳細情報を取得する';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'フィード項目からプレビュー画像、タイトル、ティーザーなどの情報を取得します。フィードに多くのテキストが含まれていない場合は、これをアクティブにできます。キーワードはフィード項目のメタヘッダーから取得され、ハッシュタグとして投稿されます。';
$a->strings['Fetch information'] = '情報を取得する';
$a->strings['Fetch keywords'] = 'キーワードを取得する';
$a->strings['Fetch information and keywords'] = '情報とキーワードを取得する';
$a->strings['No mirroring'] = 'ミラーリングなし';
$a->strings['Mirror as my own posting'] = '自分の投稿としてミラー';
$a->strings['Contact Information / Notes'] = 'コンタクト/メモ';
$a->strings['Contact Settings'] = 'コンタクト設定';
$a->strings['Contact'] = 'コンタクト';
$a->strings['Their personal note'] = '彼らの個人的なメモ';
$a->strings['Edit contact notes'] = 'コンタクトメモを編集する';
$a->strings['Block/Unblock contact'] = 'コンタクトのブロック/ブロック解除';
$a->strings['Ignore contact'] = 'コンタクトを無視';
$a->strings['View conversations'] = '会話を見る';
$a->strings['Last update:'] = '最後の更新：';
$a->strings['Update public posts'] = '一般公開の投稿を更新';
$a->strings['Update now'] = '今すぐ更新';
$a->strings['Awaiting connection acknowledge'] = '接続確認応答待ち';
$a->strings['Currently blocked'] = '現在ブロックされています';
$a->strings['Currently ignored'] = '現在無視されます';
$a->strings['Currently archived'] = '現在アーカイブ済み';
$a->strings['Hide this contact from others'] = 'このコンタクトを他の人から隠す';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = '一般公開の投稿への返信・いいねは、引き続き表示される<strong>場合が</strong>あります';
$a->strings['Notification for new posts'] = '新しい投稿の通知';
$a->strings['Send a notification of every new post of this contact'] = 'このコンタクトの新しい投稿ごとに通知を送信する';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = '「情報とキーワードの取得」が選択されている場合、ハッシュタグに変換しないキーワードのカンマ区切りリスト';
$a->strings['Actions'] = '操作';
$a->strings['Status'] = '状態';
$a->strings['Mirror postings from this contact'] = 'このコンタクトからの投稿をミラーリングする';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'このコンタクトをremote_selfとしてマークすると、friendicaがこのコンタクトから新しいエントリを再投稿します。';
$a->strings['Refetch contact data'] = 'コンタクトデータを再取得する';
$a->strings['Toggle Blocked status'] = 'ブロック状態の切り替え';
$a->strings['Toggle Ignored status'] = '無視ステータスの切り替え';
$a->strings['Bad Request.'] = '要求の形式が正しくありません。';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = '利用可能な提案はありません。新しいサイトの場合は、24時間後にもう一度お試しください。';
$a->strings['You aren\'t following this contact.'] = 'あなたはこのコンタクトをフォローしていません';
$a->strings['Unfollowing is currently not supported by your network.'] = '現在、フォロー解除はあなたのネットワークではサポートされていません';
$a->strings['Disconnect/Unfollow'] = '接続・フォローを解除';
$a->strings['No results.'] = '結果がありません。';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'このコミュニティストリームには、このノードが受信したすべての一般公開投稿が表示されます。このノードのユーザーの意見を反映していない場合があります。';
$a->strings['Community option not available.'] = 'コミュニティオプションは利用できません。';
$a->strings['Not available.'] = '利用不可。';
$a->strings['Credits'] = 'クレジット';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendicaはコミュニティプロジェクトであり、多くの人々の助けがなければ不可能です。以下は、Friendicaのコードまたは翻訳に貢献した人のリストです。皆さん、ありがとうございました！';
$a->strings['Error'] = [
	0 => 'エラー',
];
$a->strings['Source input'] = 'ソース入力';
$a->strings['BBCode::toPlaintext'] = 'BBCode :: toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode :: convert（生のHTML）';
$a->strings['BBCode::convert'] = 'BBCode :: convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode :: convert => HTML :: toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode :: toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert (raw HTML)'] = 'BBCode::toMarkdown => Markdown::convert (raw HTML)';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode :: toMarkdown => Markdown :: convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode :: toMarkdown => Markdown :: toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode :: toMarkdown => Markdown :: convert => HTML :: toBBCode';
$a->strings['Item Body'] = '項目本体';
$a->strings['Item Tags'] = '項目タグ';
$a->strings['Source input (Diaspora format)'] = 'ソース入力（Diaspora形式）';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown :: convert（生のHTML）';
$a->strings['Markdown::convert'] = 'Markdown :: convert';
$a->strings['Markdown::toBBCode'] = 'Markdown :: toBBCode';
$a->strings['Raw HTML input'] = '生のHTML入力';
$a->strings['HTML Input'] = 'HTML入力';
$a->strings['HTML::toBBCode'] = 'HTML :: toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML :: toBBCode => BBCode :: convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML :: toBBCode => BBCode :: convert（生のHTML）';
$a->strings['HTML::toBBCode => BBCode::toPlaintext'] = 'HTML :: toBBCode => BBCode :: toPlaintext';
$a->strings['HTML::toMarkdown'] = 'HTML :: toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML :: toPlaintext';
$a->strings['HTML::toPlaintext (compact)'] = 'HTML :: toPlaintext（コンパクト）';
$a->strings['Source text'] = 'ソーステキスト';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'マークダウン';
$a->strings['HTML'] = 'HTML';
$a->strings['You must be logged in to use this module'] = 'このモジュールを使用するにはログインする必要があります';
$a->strings['Source URL'] = 'ソースURL';
$a->strings['Time Conversion'] = '時間変換';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendicaは、未知のタイムゾーンで他のネットワークや友人とイベントを共有するためにこのサービスを提供します。';
$a->strings['UTC time: %s'] = 'UTC時間： %s';
$a->strings['Current timezone: %s'] = '現在のタイムゾーン： %s';
$a->strings['Converted localtime: %s'] = '変換された現地時間： %s';
$a->strings['Please select your timezone:'] = 'タイムゾーンを選択してください：';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'ログインしているユーザーのみがプローブを実行できます。';
$a->strings['Output'] = '出力';
$a->strings['Lookup address'] = 'ルックアップアドレス';
$a->strings['No entries (some entries may be hidden).'] = 'エントリなし（一部のエントリは非表示になる場合があります）';
$a->strings['Find on this site'] = 'このサイトで見つける';
$a->strings['Results for:'] = 'の結果：';
$a->strings['Site Directory'] = 'サイトディレクトリ';
$a->strings['- select -'] = '-選択-';
$a->strings['Suggested contact not found.'] = '推奨コンタクトが見つかりません。';
$a->strings['Friend suggestion sent.'] = '友達の提案が送信されました。';
$a->strings['Suggest Friends'] = '友人を示唆しています';
$a->strings['Suggest a friend for %s'] = '%s友達を提案する';
$a->strings['Installed addons/apps:'] = 'インストールされたアドオン/アプリ：';
$a->strings['No installed addons/apps'] = 'アドオン/アプリがインストールされていません';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'このノードの<a href="%1$s/tos">利用規約</a>について読んでください。';
$a->strings['On this server the following remote servers are blocked.'] = 'このサーバーでは、次のリモートサーバーがブロックされています。';
$a->strings['Reason for the block'] = 'ブロックの理由';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'これは、Webロケーション%s実行されているFriendicaバージョン%sです。データベースのバージョンは%s 、更新後のバージョンは%sです。';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Friendicaプロジェクトの詳細については、<a href="https://friendi.ca"> Friendi.ca </a>をご覧ください。';
$a->strings['Bug reports and issues: please visit'] = 'バグレポートと問題：こちらをご覧ください';
$a->strings['the bugtracker at github'] = 'githubのバグトラッカー';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = '提案、ファンレターなどを "info " at "friendi - dot - ca"でお待ちしております。';
$a->strings['No profile'] = 'プロフィールなし';
$a->strings['Method Not Allowed.'] = 'そのメソッドは許可されていません。';
$a->strings['Help:'] = 'ヘルプ:';
$a->strings['Welcome to %s'] = '%sへようこそ';
$a->strings['Friendica Communications Server - Setup'] = 'Friendica Communications Server-セットアップ';
$a->strings['System check'] = 'システムチェック';
$a->strings['Requirement not satisfied'] = '要件を満たしていない';
$a->strings['Optional requirement not satisfied'] = 'オプションの要件を満たしていない';
$a->strings['Next'] = '次';
$a->strings['Check again'] = '再び確かめる';
$a->strings['Base settings'] = '基本設定';
$a->strings['Base path to installation'] = 'インストールへの基本パス';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'システムがインストールへの正しいパスを検出できない場合は、ここに正しいパスを入力します。この設定は、制限されたシステムとWebルートへのシンボリックリンクを使用している場合にのみ設定する必要があります。';
$a->strings['Database connection'] = 'データベース接続';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'Friendicaをインストールするには、データベースへの接続方法を知る必要があります。';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'これらの設定について質問がある場合は、ホスティングプロバイダーまたはサイト管理者にお問い合わせください。';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = '以下で指定するデータベースはすでに存在している必要があります。存在しない場合は、続行する前に作成してください。';
$a->strings['Database Server Name'] = 'データベースサーバー名';
$a->strings['Database Login Name'] = 'データベースのログイン名';
$a->strings['Database Login Password'] = 'データベースログインパスワード';
$a->strings['For security reasons the password must not be empty'] = 'セキュリティ上の理由から、パスワードを空にしないでください';
$a->strings['Database Name'] = 'データベース名';
$a->strings['Please select a default timezone for your website'] = 'ウェブサイトのデフォルトのタイムゾーンを選択してください';
$a->strings['Site settings'] = 'サイト設定';
$a->strings['Site administrator email address'] = 'サイト管理者のメールアドレス';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'ウェブ管理パネルを使用するには、アカウントのメールアドレスがこれと一致する必要があります。';
$a->strings['System Language:'] = 'システムの言語：';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Friendicaインストールインターフェイスのデフォルト言語を設定し、メールを送信します。';
$a->strings['Your Friendica site database has been installed.'] = 'Friendicaサイトデータベースがインストールされました。';
$a->strings['Installation finished'] = 'インストール完了';
$a->strings['<h1>What next</h1>'] = '<h1>次は何でしょうか</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = '重要：ワーカーのスケジュールされたタスクを[手動で]設定する必要があります。';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = '新しいFriendicaノード<a href="%s/register">登録ページ</a>に移動して、新しいユーザーとして登録します。管理者の電子メールとして入力したものと同じ電子メールを使用することを忘れないでください。これにより、サイト管理者パネルに入ることができます。';
$a->strings['Total invitation limit exceeded.'] = '合計招待制限を超えました。';
$a->strings['%s : Not a valid email address.'] = '%s ：有効なメールアドレスではありません。';
$a->strings['Please join us on Friendica'] = 'Friendicaにご参加ください';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = '招待制限を超えました。サイト管理者に連絡してください。';
$a->strings['%s : Message delivery failed.'] = '%s ：メッセージの配信に失敗しました。';
$a->strings['%d message sent.'] = [
	0 => '%dメッセージを送信しました。',
];
$a->strings['You have no more invitations available'] = '利用可能な招待はもうありません';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = '参加できる公開サイトのリストについては、 %sにアクセスしてください。他のサイトのFriendicaメンバーは、他の多くのソーシャルネットワークのメンバーと同様に、お互いに接続できます。';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'この招待を受け入れるには、 %sまたはその他の公開Friendica Webサイトにアクセスして登録してください。';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Friendicaサイトはすべて相互接続して、メンバーが所有および管理する、プライバシーが強化された巨大なソーシャルWebを作成します。また、多くの従来のソーシャルネットワークに接続できます。参加できるFriendicaサイトのリストについては、 %sをご覧ください。';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = '申し訳ございません。このシステムは現在、他の公開サイトに接続したり、メンバーを招待するようには構成されていません。';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Friendicaサイトはすべて相互接続して、メンバーが所有および管理する、プライバシーが強化された巨大なソーシャルWebを作成します。また、多くの従来のソーシャルネットワークに接続できます。';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'この招待を受け入れるには、 %sアクセスして登録してください。';
$a->strings['Send invitations'] = '招待状を送信する';
$a->strings['Enter email addresses, one per line:'] = '電子メールアドレスを1行に1つずつ入力します。';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Friendicaで私や他の親しい友人と一緒に参加してください。より良いソーシャルWebの作成を手伝ってください。';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'この招待コードを提供する必要があります：$ invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = '登録したら、次のプロフィールページから接続してください。';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Friendicaプロジェクトの詳細と、それが重要だと感じる理由については、http：//friendi.caをご覧ください。';
$a->strings['Please enter a post body.'] = '投稿本文を入力してください。';
$a->strings['This feature is only available with the frio theme.'] = 'この機能は、frioテーマでのみ使用可能です。';
$a->strings['Compose new personal note'] = '新しい個人メモを作成する';
$a->strings['Compose new post'] = '新しい投稿を作成';
$a->strings['Visibility'] = '公開範囲';
$a->strings['Clear the location'] = '場所をクリアする';
$a->strings['Location services are unavailable on your device'] = 'デバイスで位置情報サービスを利用できません';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = '位置情報サービスは無効になっています。お使いのデバイスでウェブサイトの権限を確認してください';
$a->strings['The feed for this item is unavailable.'] = 'この項目のフィードは利用できません。';
$a->strings['System down for maintenance'] = 'メンテナンスのためのシステムダウン';
$a->strings['A Decentralized Social Network'] = '分権化されたソーシャルネットワーク';
$a->strings['Files'] = 'ファイル';
$a->strings['Upload'] = 'アップロードする';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'すいません、サーバのPHP設定で許可されたサイズよりも大きいファイルをアップロードしている可能性があります。';
$a->strings['Or - did you try to upload an empty file?'] = 'または、空のファイルをアップロードしようとしていませんか？';
$a->strings['File exceeds size limit of %s'] = 'ファイルサイズ上限 %s を超えています。';
$a->strings['File upload failed.'] = 'アップロードが失敗しました。';
$a->strings['Unable to process image.'] = '画像を処理できません。';
$a->strings['Image upload failed.'] = '画像アップロードに失敗しました。';
$a->strings['Normal Account Page'] = '通常のアカウントページ';
$a->strings['Soapbox Page'] = 'Soapboxページ';
$a->strings['Automatic Friend Page'] = '自動友達ページ';
$a->strings['Personal Page'] = '個人ページ';
$a->strings['Organisation Page'] = '組織ページ';
$a->strings['News Page'] = 'ニュースページ';
$a->strings['%s contact unblocked'] = [
	0 => '%s はコンタクトのブロックを解除しました',
];
$a->strings['Remote Contact Blocklist'] = 'リモートコンタクトブロックリスト';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'このページを使用すると、リモートコンタクトからのメッセージがあなたのノードに届かないようにできます。';
$a->strings['Block Remote Contact'] = 'リモートコンタクトをブロック';
$a->strings['select all'] = 'すべて選択';
$a->strings['select none'] = 'どれも選択しない';
$a->strings['No remote contact is blocked from this node.'] = 'このノードからのリモートコンタクトはブロックされていません。';
$a->strings['Blocked Remote Contacts'] = 'ブロックされたリモートコンタクト';
$a->strings['Block New Remote Contact'] = '新しいリモートコンタクトをブロック';
$a->strings['Photo'] = '写真';
$a->strings['Reason'] = '理由';
$a->strings['%s total blocked contact'] = [
	0 => '%s 件のブロック済みコンタクト',
];
$a->strings['URL of the remote contact to block.'] = 'ブロックするリモートコンタクトのURL。';
$a->strings['Block Reason'] = 'ブロックの理由';
$a->strings['Server Domain Pattern'] = 'サーバードメインパターン';
$a->strings['Block reason'] = 'ブロック理由';
$a->strings['Blocked server domain pattern'] = 'ブロックされたサーバードメインパターン';
$a->strings['Delete server domain pattern'] = 'サーバードメインパターンの削除';
$a->strings['Check to delete this entry from the blocklist'] = 'ブロックリストからこのエントリを削除する場合にチェックします';
$a->strings['Server Domain Pattern Blocklist'] = 'サーバードメインパターンブロックリスト';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'ブロックされたサーバードメインパターンのリストは、 <a href="/friendica">/friendica </a> ページで公開され、ユーザーやコミュニケーションの問題を調査している人々が理由を簡単に見つけることができます。';
$a->strings['Save changes to the blocklist'] = '変更をブロックリストに保存する';
$a->strings['Current Entries in the Blocklist'] = 'ブロックリストの現在のエントリ';
$a->strings['Item marked for deletion.'] = '削除対象としてマークされた項目。';
$a->strings['Delete this Item'] = 'この項目を削除';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'このページでは、ノードから項目を削除できます。項目がトップレベルの投稿である場合、スレッド全体が削除されます。';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = '項目のGUIDを知る必要があります。たとえば、表示URLを調べることで見つけることができます。たとえば http://example.com/display/123456 の最後の部分がGUIDであり、ここでは123456です。';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = '削除する項目のGUID。';
$a->strings['Type'] = 'タイプ';
$a->strings['Item not found'] = '項目が見つかりません';
$a->strings['Item Guid'] = '項目GUID';
$a->strings['Registered users'] = '登録ユーザー';
$a->strings['Pending registrations'] = '保留中の登録';
$a->strings['%s user blocked'] = [
	0 => '%sユーザーがブロックされました',
];
$a->strings['You can\'t remove yourself'] = '自分を削除することはできません';
$a->strings['%s user deleted'] = [
	0 => '%sユーザーが削除されました',
];
$a->strings['Register date'] = '登録日';
$a->strings['Last login'] = '前回のログイン';
$a->strings['User blocked'] = 'ユーザーがブロックされました';
$a->strings['Site admin'] = 'サイト管理者';
$a->strings['Account expired'] = 'アカウントの有効期限が切れました';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = '選択したユーザーは削除されます！

これらのユーザーがこのサイトに投稿したものはすべて完全に削除されます！

よろしいですか？';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'ユーザー{0}は削除されます！

このユーザーがこのサイトに投稿したものはすべて完全に削除されます！

よろしいですか？';
$a->strings['User "%s" deleted'] = 'ユーザー"%s"が削除されました';
$a->strings['User "%s" blocked'] = 'ユーザー"%s"がブロックされました';
$a->strings['%s user unblocked'] = [
	0 => '%sユーザーのブロックを解除しました',
];
$a->strings['User "%s" unblocked'] = 'ユーザー"%s"のブロックを解除しました';
$a->strings['New User'] = '新しいユーザー';
$a->strings['Add User'] = 'ユーザーを追加する';
$a->strings['Name of the new user.'] = '新しいユーザーの名前。';
$a->strings['Nickname'] = 'ニックネーム';
$a->strings['Nickname of the new user.'] = '新しいユーザーのニックネーム。';
$a->strings['Email address of the new user.'] = '新しいユーザーのメールアドレス。';
$a->strings['Permanent deletion'] = '永久削除';
$a->strings['User waiting for permanent deletion'] = '永久削除を待っているユーザー';
$a->strings['Account approved.'] = 'アカウントが承認されました。';
$a->strings['Request date'] = '依頼日';
$a->strings['No registrations.'] = '登録なし。';
$a->strings['Note from the user'] = 'ユーザーからのメモ';
$a->strings['Deny'] = '拒否する';
$a->strings['Show Ignored Requests'] = '無視されたリクエストを表示';
$a->strings['Hide Ignored Requests'] = '無視されたリクエストを隠す';
$a->strings['Notification type:'] = '通知の種類：';
$a->strings['Suggested by:'] = 'によって提案されました：';
$a->strings['Claims to be known to you: '] = 'あなたに知られているという主張：';
$a->strings['Shall your connection be bidirectional or not?'] = 'つながりを相互フォローにしてもよいですか？';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = '%s を友達として受け入れた場合、%s はあなたの投稿を購読できます。また、あなたのニュースフィードにこのアカウントの投稿が表示されます。';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = '%sを購読者として受け入れると、このアカウントはあなたの投稿を購読できますが、このアカウントからの投稿はあなたのニュースフィードに表示されません。';
$a->strings['Friend'] = 'ともだち';
$a->strings['Subscriber'] = '購読者';
$a->strings['No introductions.'] = '招待はありません。';
$a->strings['No more %s notifications.'] = 'これ以上%s通知はありません。';
$a->strings['You must be logged in to show this page.'] = 'このページを表示するにはログインする必要があります';
$a->strings['Network Notifications'] = 'ネットワーク通知';
$a->strings['System Notifications'] = 'システム通知';
$a->strings['Personal Notifications'] = '個人的な通知';
$a->strings['Home Notifications'] = 'ホーム通知';
$a->strings['Show unread'] = '未読を表示';
$a->strings['{0} requested registration'] = '{0}は登録をリクエストしました';
$a->strings['Authorize application connection'] = 'アプリからの接続を承認します';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'このアプリケーションによる、あなたの投稿・コンタクトの読み取りや、新しい投稿の作成を許可しますか？';
$a->strings['No contact provided.'] = 'コンタクトは提供されていません。';
$a->strings['Couldn\'t fetch information for contact.'] = 'コンタクトの情報を取得できませんでした。';
$a->strings['Couldn\'t fetch friends for contact.'] = 'コンタクトの友達関係を取得できませんでした。';
$a->strings['Done'] = '完了';
$a->strings['success'] = '成功';
$a->strings['failed'] = '失敗';
$a->strings['ignored'] = '無視';
$a->strings['Keep this window open until done.'] = 'ウィンドウを閉じずにお待ちください…';
$a->strings['The Photo with id %s is not available.'] = 'ID%sの写真は利用できません';
$a->strings['Invalid photo with id %s.'] = 'ID %s の写真が無効です。';
$a->strings['Edit post'] = '投稿を編集';
$a->strings['web link'] = 'ウェブリンク';
$a->strings['Insert video link'] = 'ビデオリンクを挿入';
$a->strings['video link'] = 'ビデオリンク';
$a->strings['Insert audio link'] = 'オーディオリンクを挿入';
$a->strings['audio link'] = 'オーディオリンク';
$a->strings['Remove Item Tag'] = 'タグの削除';
$a->strings['Select a tag to remove: '] = '削除するタグを選択:';
$a->strings['Remove'] = '削除';
$a->strings['Model not found'] = 'モジュールが見つかりません';
$a->strings['Remote privacy information not available.'] = 'リモートプライバシー情報は利用できません。';
$a->strings['Visible to:'] = '表示先：';
$a->strings['No contacts.'] = 'コンタクトはありません。';
$a->strings['%s\'s posts'] = '%sの投稿';
$a->strings['%s\'s comments'] = '%sのコメント';
$a->strings['%s\'s timeline'] = '%sのタイムライン';
$a->strings['Image exceeds size limit of %s'] = '画像サイズ上限 %s を超えています。';
$a->strings['Image upload didn\'t complete, please try again'] = '画像のアップロードが完了しませんでした。もう一度お試しください';
$a->strings['Image file is missing'] = '画像ファイルがありません';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'サーバーは現在、新しいファイルのアップロードを受け入れられません。管理者に連絡してください';
$a->strings['Image file is empty.'] = '画像ファイルが空です。';
$a->strings['View Album'] = 'アルバムを見る';
$a->strings['Profile not found.'] = 'プロフィールが見つかりません。';
$a->strings['Full Name:'] = 'フルネーム：';
$a->strings['Member since:'] = '以来のメンバー：';
$a->strings['j F, Y'] = 'j F, Y';
$a->strings['j F'] = 'j F';
$a->strings['Birthday:'] = 'お誕生日：';
$a->strings['Age: '] = '年齢：';
$a->strings['%d year old'] = [
	0 => '%d歳',
];
$a->strings['Description:'] = '説明：';
$a->strings['Profile unavailable.'] = 'プロフィールを利用できません。';
$a->strings['Invalid locator'] = '無効なロケーター';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'あなたのネットワークではリモート購読ができません。あなたのシステム上で直接購読してください。';
$a->strings['Friend/Connection Request'] = '友達/接続リクエスト';
$a->strings['If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.'] = 'まだ\'自由なソーシャルウェブ\'のメンバーでない場合は、<a href="%s">このリンクをクリックして、Friendicaの公開サイトを見つけて、今すぐ参加してください</a>。';
$a->strings['Only parent users can create additional accounts.'] = '追加アカウントを作成できるのは親ユーザのみです。';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'このサイトは、1日あたりに許可されているアカウント登録数の上限を超えています。 明日再度お試しください。';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = '（オプションで）OpenIDを提供し、「登録」をクリックして、OpenIDを介してこのフォームに入力できます。';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'OpenIDに慣れていない場合は、そのフィールドを空白のままにして、残りの項目を入力してください。';
$a->strings['Your OpenID (optional): '] = 'OpenID（オプション）：';
$a->strings['Include your profile in member directory?'] = 'メンバーディレクトリにプロフィールを含めますか？';
$a->strings['Note for the admin'] = '管理者への注意';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'このノードに参加する理由、管理者へのメッセージを残す';
$a->strings['Membership on this site is by invitation only.'] = 'このサイトのメンバーシップは招待のみです。';
$a->strings['Your invitation code: '] = '招待コード：';
$a->strings['Please repeat your e-mail address:'] = 'メールアドレスを再入力してください。';
$a->strings['New Password:'] = '新しいパスワード：';
$a->strings['Leave empty for an auto generated password.'] = '自動生成されたパスワードの場合は空のままにします。';
$a->strings['Confirm:'] = '確認：';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'プロフィールのニックネームを選択します。これはテキスト文字で始まる必要があります。このサイトのプロフィールアドレスは"<strong> nickname@%s</strong> "になります。';
$a->strings['Choose a nickname: '] = 'ニックネームを選択：';
$a->strings['Import'] = 'インポート';
$a->strings['Import your profile to this friendica instance'] = 'このfriendicaインスタンスにプロフィールをインポートします';
$a->strings['Note: This node explicitly contains adult content'] = '注：このノードには、露骨なアダルトコンテンツが含まれています';
$a->strings['Parent Password:'] = '親パスワード:';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'リクエストの確認のため、親アカウントのパスワードを入力してください。';
$a->strings['Password doesn\'t match.'] = 'パスワードが一致しません。';
$a->strings['Please enter your password.'] = 'パスワードを入力してください。';
$a->strings['You have entered too much information.'] = '入力件数が多すぎます';
$a->strings['Please enter the identical mail address in the second field.'] = '2番目の入力欄に同じメールアドレスを再入力してください。';
$a->strings['The additional account was created.'] = '追加アカウントが作成されました。';
$a->strings['Registration successful. Please check your email for further instructions.'] = '登録に成功。詳細については、メールを確認してください。';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'メールを送信できませんでした。ここでアカウントの詳細：<br>ログイン： %s <br>パスワード： %s <br> <br>ログイン後にパスワードを変更できます。';
$a->strings['Registration successful.'] = '登録に成功。';
$a->strings['Your registration can not be processed.'] = '登録を処理できません。';
$a->strings['You have to leave a request note for the admin.'] = '管理者へリクエストする内容を書く必要があります。';
$a->strings['Your registration is pending approval by the site owner.'] = '登録はサイト所有者による承認待ちです。';
$a->strings['You must be logged in to use this module.'] = 'このモジュールを使用するにはログインする必要があります';
$a->strings['Only logged in users are permitted to perform a search.'] = 'ログインしたユーザーのみが検索を実行できます。';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'ログインしていないユーザーには、1分間に1つの検索のみが許可されます。';
$a->strings['Items tagged with: %s'] = 'タグ付けされた項目： %s';
$a->strings['Search term already saved.'] = 'すでに保存された検索キーワードです。';
$a->strings['Create a New Account'] = '新しいアカウントを作成する';
$a->strings['Your OpenID: '] = 'あなたの OpenID: ';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'ユーザー名とパスワードを入力して、既存のアカウントにOpenIDを追加してください。';
$a->strings['Or login using OpenID: '] = 'または、OpenIDを使用してログインします。';
$a->strings['Password: '] = 'パスワード：';
$a->strings['Remember me'] = '次から自動的にログイン';
$a->strings['Forgot your password?'] = 'パスワードをお忘れですか？';
$a->strings['Website Terms of Service'] = 'ウェブサイト利用規約';
$a->strings['terms of service'] = '利用規約';
$a->strings['Website Privacy Policy'] = 'ウェブサイトのプライバシーポリシー';
$a->strings['privacy policy'] = '個人情報保護方針';
$a->strings['Logged out.'] = 'ログアウトしました。';
$a->strings['OpenID protocol error. No ID returned'] = 'OpenID プロトコルエラー。返答にてIDが返されませんでした。';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'アカウントが見つかりませんでした。 既存のアカウントにログインして、OpenIDを追加してください。';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'アカウントが見つかりませんでした。 OpenIDを追加するには、新しいアカウントを登録するか、既存のアカウントにログインしてください。';
$a->strings['Passwords do not match.'] = 'パスワードが一致していません。';
$a->strings['Password unchanged.'] = 'パスワードは変更されていません。';
$a->strings['Current Password:'] = '現在のパスワード：';
$a->strings['Your current password to confirm the changes'] = '変更を確認するための現在のパスワード';
$a->strings['Remaining recovery codes: %d'] = '残りの復旧コード： %d';
$a->strings['Invalid code, please retry.'] = '無効なコードです。再試行してください。';
$a->strings['Two-factor recovery'] = '二要素回復';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>モバイルデバイスにアクセスできなくなった場合に備えて、ワンタイムリカバリコードのいずれかを入力できます。</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'お使いの携帯電話を持ってませんか？ <a href="%s">二要素認証の回復コードを入力</a>';
$a->strings['Please enter a recovery code'] = '復旧コードを入力してください';
$a->strings['Submit recovery code and complete login'] = '復旧コードを送信してログインを完了する';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>デバイスで二要素認証アプリを開き、認証コードを取得して本人確認を行います。</p>';
$a->strings['Please enter a code from your authentication app'] = '認証アプリからコードを入力してください';
$a->strings['Verify code and complete login'] = 'コードを確認してログインを完了する';
$a->strings['Please use a shorter name.'] = '短い名前を使用してください。';
$a->strings['Name too short.'] = '名前が短すぎます。';
$a->strings['Wrong Password.'] = 'パスワードが間違っています。';
$a->strings['Invalid email.'] = '無効なメール。';
$a->strings['Cannot change to that email.'] = 'そのメールに変更できません。';
$a->strings['Settings were not updated.'] = '設定が更新されませんでした。';
$a->strings['Relocate message has been send to your contacts'] = '再配置メッセージがコンタクトに送信されました';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'プロフィールが見つかりません。管理者に連絡してください。';
$a->strings['Personal Page Subtypes'] = '個人ページのサブタイプ';
$a->strings['Account for a personal profile.'] = '個人プロフィールを説明します。';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'コンタクトリクエストを「フォロワー」として自動的に承認します。組織に適したアカウントです。';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'コンタクトのリクエストを「フォロワー」として自動的に承認します。ニュース再配信に適したアカウントです。';
$a->strings['Account for community discussions.'] = 'コミュニティディスカッションのアカウント。';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = '"Friends "および"Followers "の手動承認を必要とする通常の個人プロフィールのアカウント。';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'コンタクトリクエストを「フォロワー」として自動的に承認します。一般公開プロフィールのアカウントです。';
$a->strings['Automatically approves all contact requests.'] = 'すべてのコンタクトリクエストを自動的に承認します。';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'コンタクトのリクエストを「フレンド」として自動的に承認します。知名度のあるプロフィールに適したアカウントです。';
$a->strings['Requires manual approval of contact requests.'] = 'コンタクトリクエストの手動承認が必要です。';
$a->strings['OpenID:'] = 'OpenID：';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '（オプション）このOpenIDがこのアカウントにログインできるようにします。';
$a->strings['Publish your profile in your local site directory?'] = 'ローカルサイトディレクトリにプロフィールを公開しますか？';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'プロフィールはこのノードの<a href="%s">ローカルディレクトリ</a>で公開されます。システム設定によっては、プロフィールの詳細が公開される場合があります。';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'あなたのプロフィールはグローバルなFriendicaディレクトリに公開されます（例：<a href="%s"> %s </a>）。';
$a->strings['Account Settings'] = 'アカウント設定';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'IDアドレスは<strong> \' %s \' </strong>または \' %s \'です。';
$a->strings['Password Settings'] = 'パスワード設定';
$a->strings['Leave password fields blank unless changing'] = '変更しない限り、パスワードフィールドは空白のままにしてください';
$a->strings['Password:'] = 'パスワード：';
$a->strings['Your current password to confirm the changes of the email address'] = '変更を確認するための電子メールアドレスの現在のパスワード';
$a->strings['Delete OpenID URL'] = 'OpenID URLを削除';
$a->strings['Basic Settings'] = '基本設定';
$a->strings['Display name:'] = '表示名:';
$a->strings['Email Address:'] = '電子メールアドレス：';
$a->strings['Your Timezone:'] = 'あなたのタイムゾーン：';
$a->strings['Your Language:'] = 'あなたの言語：';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'friendicaインターフェイスを表示し、メールを送信するために使用する言語を設定します';
$a->strings['Default Post Location:'] = 'デフォルトの投稿場所：';
$a->strings['Use Browser Location:'] = 'ブラウザのロケーションを使用：';
$a->strings['Security and Privacy Settings'] = 'セキュリティとプライバシーの設定';
$a->strings['Maximum Friend Requests/Day:'] = '1日あたりの友達リクエスト上限：';
$a->strings['(to prevent spam abuse)'] = '（スパムの悪用を防ぐため）';
$a->strings['Allow your profile to be searchable globally?'] = '自分のプロフィールを世界中で検索できるようにしますか？';
$a->strings['Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.'] = '他の人があなたを簡単に見つけてフォローできるようにしたい場合は、この設定を有効にしてください。あなたのプロフィールはリモートシステムで検索可能です。この設定は、Friendicaが検索エンジンにあなたのプロフィールをインデックス化するかどうかも決定します。';
$a->strings['Hide your contact/friend list from viewers of your profile?'] = 'プロフィールの閲覧者からコンタクト/友人リストを非表示にしますか？';
$a->strings['A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.'] = '自分のプロフィールページには、コンタクトリストが表示されます。このオプションを有効にすると、コンタクトリストの表示が無効になります。';
$a->strings['Make public posts unlisted'] = '公開投稿を非表示にする';
$a->strings['Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.'] = '公開された投稿は、コミュニティページや検索結果には表示されず、中継サーバーにも送信されません。ただし、リモートサーバーの公開フィードには表示されます。';
$a->strings['Make all posted pictures accessible'] = '投稿した写真は全てアクセス可能にする';
$a->strings['This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can\'t handle permissions on pictures. Non public pictures still won\'t be visible for the public on your photo albums though.'] = 'このオプションは、投稿したすべての写真をダイレクトリンクでアクセスできるようにします。これは、他の多くのネットワークが写真のパーミッションを処理できないという問題を回避するためのものです。ただし、公開していない写真はフォトアルバムでは一般に公開されません。';
$a->strings['Allow friends to post to your profile page?'] = '友人があなたのプロフィールページに投稿することを許可しますか？';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'コンタクトは、プロフィールウォールに投稿を書くことができます。これらの投稿はコンタクトに配信されます';
$a->strings['Allow friends to tag your posts?'] = '友達があなたの投稿にタグを付けることを許可しますか？';
$a->strings['Your contacts can add additional tags to your posts.'] = 'コンタクトは、投稿にタグを追加できます。';
$a->strings['Default Post Permissions'] = '投稿の既定の権限';
$a->strings['Expiration settings'] = '有効期限設定';
$a->strings['Automatically expire posts after this many days:'] = 'この数日後に投稿を自動的に期限切れにします：';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = '空の場合、投稿は期限切れになりません。期限切れの投稿は削除されます';
$a->strings['Expire posts'] = '投稿の有効期限';
$a->strings['When activated, posts and comments will be expired.'] = '有効にすると、投稿とコメントは期限切れになるでしょう。';
$a->strings['Expire personal notes'] = '個人メモの有効期限';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = '有効にすると、プロフィールページ上の個人メモは期限切れになるでしょう。';
$a->strings['Expire starred posts'] = 'スター付き投稿の有効期限';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = '投稿にスターを付けると、投稿が期限切れにならないようにします。動作はこの設定で上書きされます。';
$a->strings['Only expire posts by others'] = '他のユーザーによる投稿のみを期限切れにする';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = '有効にすると、自分の投稿は期限切れになりません。そうすると、上記の設定は自分が受け取った投稿に対してのみ有効となります。';
$a->strings['Notification Settings'] = '通知設定';
$a->strings['Send a notification email when:'] = '次の場合に通知メールを送信します。';
$a->strings['You receive an introduction'] = '招待を受けます';
$a->strings['Your introductions are confirmed'] = 'あなたの招待が確認されました';
$a->strings['Someone writes on your profile wall'] = '誰かがあなたのプロフィールウォールに書き込みます';
$a->strings['Someone writes a followup comment'] = '誰かがフォローアップコメントを書く';
$a->strings['You receive a private message'] = 'プライベートメッセージを受け取ります';
$a->strings['You receive a friend suggestion'] = '友達の提案を受け取ります';
$a->strings['You are tagged in a post'] = 'あなたは投稿でタグ付けされています';
$a->strings['Activate desktop notifications'] = 'デスクトップ通知を有効にする';
$a->strings['Show desktop popup on new notifications'] = '新しい通知にデスクトップポップアップを表示する';
$a->strings['Text-only notification emails'] = 'テキストのみの通知メール';
$a->strings['Send text only notification emails, without the html part'] = 'HTML部分なしで、テキストのみの通知メールを送信します';
$a->strings['Show detailled notifications'] = '詳細な通知を表示';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'デフォルトでは、通知は項目ごとに1つの通知にまとめられます。有効にすると、すべての通知が表示されます。';
$a->strings['Show notifications of ignored contacts'] = '無視されたコンタクトの通知を表示';
$a->strings['You don\'t see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.'] = '無視されたコンタクトからの投稿は表示されません。しかし、相手のコメントは表示されます。この設定では、無視されたコンタクトからの通知を定期的に受け取るかどうかを設定します。';
$a->strings['Advanced Account/Page Type Settings'] = 'アカウント/ページタイプの詳細設定';
$a->strings['Change the behaviour of this account for special situations'] = '特別な状況でこのアカウントの動作を変更する';
$a->strings['Relocate'] = '再配置';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'このプロフィールを別のサーバーから移動し、コンタクトの一部が更新を受信しない場合は、このボタンを押してみてください。';
$a->strings['Resend relocate message to contacts'] = '再配置メッセージをコンタクトに再送信する';
$a->strings['Addon Settings'] = 'アドオン設定';
$a->strings['No Addon settings configured'] = 'アドオン設定は構成されていません';
$a->strings['Description'] = '説明';
$a->strings['Add'] = '追加';
$a->strings['Failed to connect with email account using the settings provided.'] = '提供された設定を使用してメールアカウントに接続できませんでした。';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora（Socialhome、Hubzilla）';
$a->strings['Email access is disabled on this site.'] = 'このサイトではメールアクセスが無効になっています。';
$a->strings['None'] = '無し';
$a->strings['General Social Media Settings'] = '一般的なソーシャルメディア設定';
$a->strings['Attach the link title'] = 'リンクの件名を添付します';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = '有効にすると、添付されたリンクのタイトルがDiasporaへの投稿のタイトルとして追加されます。 これは主に、フィードコンテンツを共有する「リモート セルフ」コンタクトで役立ちます。';
$a->strings['Email/Mailbox Setup'] = 'メール/メールボックスのセットアップ';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'このサービス（オプション）を使用してメールコンタクトと通信する場合は、メールボックスへの接続方法を指定してください。';
$a->strings['Last successful email check:'] = '最後に成功したメールチェック：';
$a->strings['IMAP server name:'] = 'IMAPサーバー名：';
$a->strings['IMAP port:'] = 'IMAPポート：';
$a->strings['Security:'] = 'セキュリティ：';
$a->strings['Email login name:'] = 'メールのログイン名：';
$a->strings['Email password:'] = 'メールのパスワード：';
$a->strings['Reply-to address:'] = '返信先アドレス：';
$a->strings['Send public posts to all email contacts:'] = 'すべてのメールコンタクトに一般公開投稿を送信します。';
$a->strings['Action after import:'] = 'インポート後のアクション：';
$a->strings['Move to folder'] = 'フォルダへ移動';
$a->strings['Move to folder:'] = 'フォルダへ移動：';
$a->strings['Contact CSV file upload error'] = 'アップロードエラー：コンタクトCSVファイル';
$a->strings['Importing Contacts done'] = 'コンタクトのインポートが完了しました';
$a->strings['Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'] = '古いアカウントからエクスポートしたCSVファイルをアップロードします。これは最初の列に、フォローしているアカウントのハンドルを含みます。';
$a->strings['Upload File'] = 'ファイルをアップロード';
$a->strings['Delegation successfully granted.'] = '委任が正常に許可されました。';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = '親ユーザーが見つからないか、利用できないか、パスワードが一致しません。';
$a->strings['Delegation successfully revoked.'] = '委任が正常に取り消されました。';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = '委任された管理者は、委任権限を確認できますが、変更はできません。';
$a->strings['Delegate user not found.'] = '移譲ユーザーが見つかりません。';
$a->strings['No parent user'] = '親となるユーザが存在しません。';
$a->strings['Parent User'] = '親ユーザ';
$a->strings['Additional Accounts'] = '追加のアカウント';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = '既存のアカウントに自動的に接続される追加のアカウントを登録して、このアカウントから管理できるようにします。';
$a->strings['Register an additional account'] = '追加アカウントの登録';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = '親ユーザは、このアカウントについてアカウント設定を含む全ての権限を持ちます。 このアクセスを許可するユーザ名を再確認してください。';
$a->strings['Delegates'] = '移譲';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = '移譲された人は、このアカウント/ページの管理について、基本的なアカウント設定を除いた、すべての権限を得ます。 完全に信頼していない人には、あなたの個人アカウントを移譲しないでください。';
$a->strings['Existing Page Delegates'] = '既存のページの移譲';
$a->strings['Potential Delegates'] = '移譲先の候補';
$a->strings['No entries.'] = 'エントリは有りません。';
$a->strings['The theme you chose isn\'t available.'] = '選択したテーマは使用できません。';
$a->strings['%s - (Unsupported)'] = '%s （サポートされていません）';
$a->strings['Display Settings'] = 'ディスプレイの設定';
$a->strings['General Theme Settings'] = '一般的なテーマ設定';
$a->strings['Custom Theme Settings'] = 'カスタムテーマ設定';
$a->strings['Content Settings'] = 'コンテンツ設定';
$a->strings['Theme settings'] = 'テーマ設定';
$a->strings['Display Theme:'] = 'ディスプレイテーマ：';
$a->strings['Mobile Theme:'] = 'モバイルテーマ：';
$a->strings['Number of items to display per page:'] = 'ページごとに表示する項目の数：';
$a->strings['Maximum of 100 items'] = '最大100項目';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'モバイルデバイスから表示したときにページごとに表示する項目の数：';
$a->strings['Infinite scroll'] = '無限スクロール';
$a->strings['Automatic fetch new items when reaching the page end.'] = 'ページの最後に到達したとき、新規項目を自動取得する';
$a->strings['Beginning of week:'] = '週の始まり：';
$a->strings['Additional Features'] = '追加機能';
$a->strings['Connected Apps'] = '接続されたアプリ';
$a->strings['Remove authorization'] = '承認を削除';
$a->strings['(click to open/close)'] = '（クリックして開く・閉じる）';
$a->strings['Profile Actions'] = 'プロフィールアクション';
$a->strings['Edit Profile Details'] = 'プロフィールの詳細を編集';
$a->strings['Change Profile Photo'] = 'プロフィール写真の変更';
$a->strings['Profile picture'] = 'プロフィールの写真';
$a->strings['Location'] = '位置情報';
$a->strings['Miscellaneous'] = 'その他';
$a->strings['Upload Profile Photo'] = 'プロフィール写真をアップロード';
$a->strings['Street Address:'] = '住所：';
$a->strings['Locality/City:'] = '地域/市：';
$a->strings['Region/State:'] = '地域/州：';
$a->strings['Postal/Zip Code:'] = '郵便番号：';
$a->strings['Country:'] = '国：';
$a->strings['XMPP (Jabber) address:'] = 'XMPP（Jabber）アドレス：';
$a->strings['Homepage URL:'] = 'ホームページのURL：';
$a->strings['Public Keywords:'] = '公開キーワード：';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '（友人を候補を提案するために使用ます。また他の人が見ることができます。）';
$a->strings['Private Keywords:'] = 'プライベートキーワード：';
$a->strings['(Used for searching profiles, never shown to others)'] = '（プロフィールの検索に使用され、他のユーザーには表示されません）';
$a->strings['Image size reduction [%s] failed.'] = '画像サイズの縮小[ %s ]に失敗しました。';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = '新しい写真がすぐに表示されない場合は、Shiftキーを押しながらページをリロードするか、ブラウザーのキャッシュをクリアします。';
$a->strings['Unable to process image'] = '画像を処理できません';
$a->strings['Crop Image'] = 'クロップ画像';
$a->strings['Please adjust the image cropping for optimum viewing.'] = '最適な表示になるように画像のトリミングを調整してください。';
$a->strings['Upload Profile Picture'] = 'プロフィール画像をアップロード';
$a->strings['or'] = 'または';
$a->strings['skip this step'] = 'このステップを飛ばす';
$a->strings['select a photo from your photo albums'] = 'フォトアルバムから写真を選択する';
$a->strings['[Friendica System Notify]'] = '[Friendica システム通知]';
$a->strings['User deleted their account'] = 'このユーザはアカウントを削除しました。';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Friendicaノードで、ユーザーがアカウントを削除しました。 それらのデータがバックアップから削除されていることを確認してください。';
$a->strings['The user id is %d'] = 'ユーザIDは %d です';
$a->strings['Remove My Account'] = '自分のアカウントを削除します';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'これにより、アカウントが完全に削除されます。 これが完了すると、回復できなくなります。';
$a->strings['Please enter your password for verification:'] = '確認のため、あなたのパスワードを入力してください。';
$a->strings['Please enter your password to access this page.'] = 'このページにアクセスするには、パスワードを入力してください。';
$a->strings['App-specific password generation failed: The description is empty.'] = 'アプリ固有のパスワード生成に失敗しました：説明は空です。';
$a->strings['App-specific password generation failed: This description already exists.'] = 'アプリ固有のパスワード生成に失敗しました：この説明は既に存在します。';
$a->strings['App-specific passwords successfully revoked.'] = 'アプリ固有のパスワードが正常に取り消されました。';
$a->strings['App-specific password successfully revoked.'] = 'アプリ固有のパスワードが正常に取り消されました。';
$a->strings['Two-factor app-specific passwords'] = '二要素アプリ固有のパスワード';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>アプリ固有のパスワードは、二要素認証をサポートしないサードパーティアプリケーションでアカウントを認証するために、通常のパスワードの代わりに使用されるランダムに生成されたパスワードです。</p>';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = '今すぐ新しいアプリ固有のパスワードをコピーしてください。あなたは再びそれを見ることができなくなります！';
$a->strings['Last Used'] = '最終使用';
$a->strings['Revoke'] = '取り消す';
$a->strings['Revoke All'] = 'すべて取り消す';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = '新しいアプリ固有のパスワードを生成するときは、すぐに使用する必要があります。生成後、一度表示されます。';
$a->strings['Generate new app-specific password'] = '新しいアプリ固有のパスワードを生成する';
$a->strings['Friendiqa on my Fairphone 2...'] = 'フェアフォン2のFriendiqa ...';
$a->strings['Generate'] = '生成する';
$a->strings['Two-factor authentication successfully disabled.'] = '二要素認証が正常に無効になりました。';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>ログイン時にプロンプトが表示されたら、モバイルデバイスのアプリケーションを使用して二要素認証コードを取得します。</p>';
$a->strings['Authenticator app'] = '認証アプリ';
$a->strings['Configured'] = '設定済み';
$a->strings['Not Configured'] = '設定されていません';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>認証アプリの設定が完了していません。</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>認証アプリが正しく構成されています。</p>';
$a->strings['Recovery codes'] = '回復コード';
$a->strings['Remaining valid codes'] = '残りの有効なコード';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>これらの使い捨てコードは、認証アプリのコードにアクセスできなくなった場合に、認証アプリのコードを置き換えることができます。</p>';
$a->strings['App-specific passwords'] = 'アプリ固有のパスワード';
$a->strings['Generated app-specific passwords'] = '生成されたアプリ固有のパスワード';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>これらのランダムに生成されたパスワードを使用すると、二要素認証をサポートしていないアプリで認証できます。</p>';
$a->strings['Current password:'] = '現在のパスワード：';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = '二要素認証設定を変更するには、現在のパスワードを入力する必要があります。';
$a->strings['Enable two-factor authentication'] = '二要素認証を有効にする';
$a->strings['Disable two-factor authentication'] = '二要素認証を無効にする';
$a->strings['Show recovery codes'] = '復旧コードを表示';
$a->strings['Manage app-specific passwords'] = 'アプリ固有のパスワードを管理する';
$a->strings['Finish app configuration'] = 'アプリの構成を完了する';
$a->strings['New recovery codes successfully generated.'] = '新しい回復コードが正常に生成されました。';
$a->strings['Two-factor recovery codes'] = '二要素回復コード';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>リカバリコードは、デバイスへのアクセスを失い、二要素認証コードを受信できない場合にアカウントにアクセスするために使用できます。</p> <p> <strong>これらを安全な場所に置いてください！</strong >デバイスを紛失し、復旧コードをお持ちでない場合、アカウントにアクセスできなくなります。</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = '新しい回復コードを生成する場合、新しいコードをコピーする必要があります。古いコードはもう機能しません。';
$a->strings['Generate new recovery codes'] = '新しい回復コードを生成する';
$a->strings['Next: Verification'] = '次：検証';
$a->strings['Two-factor authentication successfully activated.'] = '二要素認証が正常にアクティブ化されました。';
$a->strings['<p>Or you can submit the authentication settings manually:</p>
<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>'] = '<p>または認証設定を手動で送信できます：<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = '二要素コード検証';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>このQRコードを認証アプリでスキャンして、提供されたコードを送信してください。</p>';
$a->strings['<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>'] = '<p>または、モバイルデバイスで次のURLを開くことができます。</p> <p> <a href="%s"> %s </a> </p>';
$a->strings['Verify code and enable two-factor authentication'] = 'コードを確認し、二要素認証を有効にします';
$a->strings['Export account'] = 'アカウントのエクスポート';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'アカウント情報とコンタクトをエクスポートします。これを使用して、アカウントのバックアップを作成したり、別のサーバーに移動したりします。';
$a->strings['Export all'] = 'すべてエクスポート';
$a->strings['Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)'] = 'アカウント情報、コンタクト、すべてのアイテムをjsonとしてエクスポートします。非常に大きなファイルになる可能性があり、時間がかかる可能性があります。これを使用して、アカウントの完全バックアップを作成します（写真はエクスポートされません）';
$a->strings['Export Contacts to CSV'] = '連絡先をCSV形式でエクスポート';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'フォローしているアカウントのリストをCSVファイルとしてエクスポートします。 マストドンなどに対応します。';
$a->strings['Go back'] = '戻る';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = '登録時、およびユーザーアカウントとコンタクト間の通信を提供するために、ユーザーは表示名（ペンネーム）、ユーザー名（ニックネーム）、および有効な電子メールアドレスを提供する必要があります。
他のプロフィールの詳細が表示されていなくても、ページの訪問者はアカウントのプロフィールページで名前にアクセスできます。
電子メールアドレスは、インタラクションに関するユーザー通知の送信にのみ使用されますが、表示されることはありません。
ノードのユーザーディレクトリまたはグローバルユーザーディレクトリでのアカウントのリストはオプションであり、ユーザー設定で制御できます。通信には必要ありません。';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'このデータは通信に必要であり、通信パートナーのノードに渡されてそこに保存されます。ユーザーは、通信パートナーアカウントに送信される可能性のある追加のプライベートデータを入力できます。';
$a->strings['Privacy Statement'] = 'プライバシーに関する声明';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = '要求された項目は存在しないか、削除されました。';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'アカウントの詳細を共有する、または「管理」権限が付与されているさまざまなIDまたはコミュニティ/グループページを切り替える';
$a->strings['Select an identity to manage: '] = '管理するIDを選択します。';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'クローズドなサーバでのユーザーインポートは、管理者のみが実行できます。';
$a->strings['Move account'] = 'アカウントの移動';
$a->strings['You can import an account from another Friendica server.'] = '別のFriendicaサーバーからアカウントをインポートできます。';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = '古いサーバからアカウントをエクスポートして、このサーバにアップロードする必要があります。 アップロード後、このサーバが、すべてのコンタクト・元のアカウントを再作成します。 また、あなたがこのサーバに移転したことを友人にお知らせします。';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'この機能はまだ実験的なものです。 OStatusネットワーク（GNU Social / Statusnet）またはDiasporaからのコンタクトはインポートできません。';
$a->strings['Account file'] = 'アカウントファイル';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'アカウントをエクスポートするには、「設定」->「個人データのエクスポート」に進み、「アカウントのエクスポート」を選択します';
$a->strings['Error decoding account file'] = 'アカウントファイルのデコードエラー';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'エラー！ファイルにバージョンデータがありません！これはFriendicaアカウントファイルではなさそうです。';
$a->strings['User \'%s\' already exists on this server!'] = 'ユーザー \'%s\' はこのサーバーに既に存在します！';
$a->strings['User creation error'] = 'ユーザ作成エラー';
$a->strings['%d contact not imported'] = [
	0 => '%dコンタクトはインポートされませんでした',
];
$a->strings['User profile creation error'] = 'ユーザープロフィール作成エラー';
$a->strings['Done. You can now login with your username and password'] = '完了しました。これでであなたのユーザー名とパスワードでログインできます。 ';
$a->strings['Welcome to Friendica'] = 'Friendicaへようこそ';
$a->strings['New Member Checklist'] = '新しく参加した人のチェックリスト';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = '私たちはあなたの経験を楽しいものにするためのいくつかのヒントとリンクを提供したいと思います。項目をクリックして、関連するページにアクセスします。このページへのリンクは、最初の登録後2週間、ホームページから表示され、その後静かに消えます。';
$a->strings['Getting Started'] = 'はじめに';
$a->strings['Friendica Walk-Through'] = 'Friendica ウォークスルー';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = '<em>クイックスタート</em>ページで、プロフィールとネットワークタブの簡単な紹介を見つけ、新しい接続を作成し、参加するグループを見つけます。';
$a->strings['Go to Your Settings'] = '設定に移動';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = '[<em>設定</em>]ページで、初期パスワードを変更します。また、IDアドレスを書き留めます。これはメールアドレスのように見えます。無料のソーシャルウェブで友達を作るのに役立ちます。';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = '他の設定、特にプライバシー設定を確認してください。公開されていないディレクトリ一覧は、一覧にない電話番号を持っているようなものです。一般に、おそらくあなたのリストを公開する必要があります-あなたの友人や潜在的な友人全員があなたを見つける方法を正確に知っていない限り。';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'まだプロフィール写真をアップロードしていない場合はアップロードします。研究では、自分の実際の写真を持っている人は、持っていない人よりも友達を作る可能性が10倍高いことが示されています。';
$a->strings['Edit Your Profile'] = 'プロフィールを編集する';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'お好みに合わせて<strong>既定の</strong>プロフィールを編集します。友達のリストを非表示にし、未知の訪問者からプロフィールを非表示にするための設定を確認します。';
$a->strings['Profile Keywords'] = 'プロフィールキーワード';
$a->strings['Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'] = 'あなたの興味を説明するいくつかの公開キーワードをプロフィールに設定します。同様の興味を持つ他の人を見つけ、友情を提案することができるかもしれません。';
$a->strings['Connecting'] = '接続中';
$a->strings['Importing Emails'] = 'メールのインポート';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'メールの受信トレイから友人やメーリングリストをインポートしてやり取りする場合は、コネクタ設定ページでメールアクセス情報を入力します';
$a->strings['Go to Your Contacts Page'] = 'コンタクトページに移動します';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'コンタクトページは、友情を管理し、他のネットワーク上の友だちとつながるための入り口です。通常、<em>新しいコンタクトの追加</em>ダイアログにアドレスまたはサイトのURLを入力します。';
$a->strings['Go to Your Site\'s Directory'] = 'サイトのディレクトリに移動します';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'ディレクトリ ページでは、このネットワークまたは他のフェデレーションサイト内の他のユーザーを検索できます。プロフィールページで<em>接続</em>または<em>フォロー</em>リンクを探します。要求された場合、独自のIdentityアドレスを提供します。';
$a->strings['Finding New People'] = '新しい人を見つける';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'コンタクトページのサイドパネルには、新しい友達を見つけるためのいくつかのツールがあります。関心ごとに人を照合し、名前または興味ごとに人を検索し、ネットワーク関係に基づいて提案を提供できます。新しいサイトでは、通常24時間以内に友人の提案が表示され始めます。';
$a->strings['Why Aren\'t My Posts Public?'] = '投稿が一般に公開されないのはなぜですか？';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendicaはあなたのプライバシーを尊重します。デフォルトでは、投稿は友達として追加した人にのみ表示されます。詳細については、上記のリンクのヘルプセクションを参照してください。';
$a->strings['Getting Help'] = 'ヘルプを得る';
$a->strings['Go to the Help Section'] = 'ヘルプセクションに移動';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'プログラムのその他の機能やリソースの詳細については、<strong>ヘルプ</strong>ページをご覧ください。';
$a->strings['%s liked %s\'s post'] = '%sが%sの投稿を高く評価しました';
$a->strings['%s disliked %s\'s post'] = '%sは%sの投稿を好きではないようです';
$a->strings['%s is attending %s\'s event'] = '%sは%sのイベントに参加しています';
$a->strings['%s is not attending %s\'s event'] = '%sは%sのイベントを欠席します';
$a->strings['%s is now friends with %s'] = '%sは%sと友達になりました';
$a->strings['%s commented on %s\'s post'] = '%sが%sの投稿にコメントしました';
$a->strings['%s created a new post'] = '%sが新しい投稿を作成しました';
$a->strings['Friend Suggestion'] = '友達の提案';
$a->strings['Friend/Connect Request'] = 'フレンド/接続リクエスト';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s さんが %2$s に あなたにプライベートメッセージを送りました';
$a->strings['a private message'] = 'プライベートメッセージ';
$a->strings['%1$s sent you %2$s.'] = '%1$s があなたに %2$s を送りました';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = '%s を開いて、プライベートメッセージを確認・返信してください';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s さんが、あなたがフォローしている項目/会話にコメントしました';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = ' %s を開いて、コメントを確認・返信してください';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s が %2$s に あなたのプロフィールウォールへ投稿しました';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s が [url=%2$s]あなたのウォール[/url] に投稿しました';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = '\'%1$s\' から %2$s に 招待が来ています';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = '[url=%1$s]招待[/url] が %2$s から来ています。';
$a->strings['You may visit their profile at %s'] = '彼らのプロフィールを %s にて開けるかもしれません。';
$a->strings['Please visit %s to approve or reject the introduction.'] = ' %s を開いて、招待を承諾・拒否してください。';
$a->strings['%1$s is sharing with you at %2$s'] = '%1$s さんが %2$s にて あなたの投稿を共有しました';
$a->strings['You have a new follower at %2$s : %1$s'] = '新しいフォロワーです %2$s : %1$s';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = '\'%1$s\' より %2$s に 友達の候補を受け取りました';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = '%3$s から %2$s への [url=%1$s]友達の候補[/url] を受け取りました。';
$a->strings['Name:'] = '名前:';
$a->strings['Photo:'] = '写真:';
$a->strings['Please visit %s to approve or reject the suggestion.'] = '%s を開いて、候補を承諾・拒否してください。';
$a->strings['%s Connection accepted'] = '%s つながりが承諾されました';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '\'%1$s\' は %2$s に あなたからのつながりの申込みを承諾しました';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s は あなたからの [url=%1$s]つながりの申し込み[/url] を承諾しました。';
$a->strings['You are now mutual friends and may exchange status updates, photos, and email without restriction.'] = 'あなたたちは友達になりました。ステータスの更新、写真、メールを制限なくやりとりできます。';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'このつながりを変更する場合は %s を開いてください。';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' はあなたをファンとして受け入れました。プライベートメッセージやプロフィール インタラクションなど、一部のやりとりは制限されています。 有名人またはコミュニティページの場合、これらの設定は自動的に適用されます。';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '\'%1$s\' は後日、これを双方向・より寛容な関係へと拡張する場合があります。';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'このつながりを変更する場合は %s を開いてください。';
$a->strings['registration request'] = '登録リクエスト';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = '\'%1$s\' より %2$s に 登録リクエストを受け取りました';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = '[url=%1$s]登録リクエスト[/url] が %2$s から来ています。';
$a->strings['Please visit %s to approve or reject the request.'] = '%s を開いて、リクエストを承諾・拒否してください。';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'このメッセージは、Friendicaソーシャルネットワークのメンバーである%sから送信されました。';
$a->strings['You may visit them online at %s'] = 'あなたは%sでそれらをオンラインで訪れることができます';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'これらのメッセージを受信したくない場合は、この投稿に返信して送信者に連絡してください。';
$a->strings['%s posted an update.'] = '%sが更新を投稿しました。';
$a->strings['Private Message'] = '自分のみ';
$a->strings['This entry was edited'] = 'このエントリは編集されました';
$a->strings['Edit'] = '編集';
$a->strings['Delete globally'] = 'グローバルに削除';
$a->strings['Remove locally'] = 'ローカルで削除';
$a->strings['Save to folder'] = 'フォルダーに保存';
$a->strings['I will attend'] = '参加します';
$a->strings['I will not attend'] = '私は出席しません';
$a->strings['I might attend'] = '私は出席するかもしれません';
$a->strings['Ignore thread'] = 'スレッドを無視';
$a->strings['Unignore thread'] = '無視しないスレッド';
$a->strings['%s (Received %s)'] = '%s (%s を受け取りました)';
$a->strings['to'] = 'に';
$a->strings['via'] = '投稿先:';
$a->strings['Wall-to-Wall'] = '壁間';
$a->strings['via Wall-To-Wall:'] = 'Wall-to-Wall経由：';
$a->strings['Reply to %s'] = '%sへの返信';
$a->strings['Notifier task is pending'] = '通知タスクは保留中です';
$a->strings['Delivery to remote servers is pending'] = 'リモートサーバーへの配信は保留中です';
$a->strings['Delivery to remote servers is underway'] = 'リモートサーバーへの配信が進行中です';
$a->strings['Delivery to remote servers is mostly done'] = 'リモートサーバーへの配信はもうすぐ完了します';
$a->strings['Delivery to remote servers is done'] = 'リモートサーバーへの配信が完了しました';
$a->strings['%d comment'] = [
	0 => '%dコメント',
];
$a->strings['Show more'] = 'もっと見せる';
$a->strings['Show fewer'] = '表示を減らす';
$a->strings['Login failed.'] = 'ログインに失敗しました。';
$a->strings['Login failed. Please check your credentials.'] = 'ログインに失敗しました。認証情報を確かめてください。';
$a->strings['Welcome %s'] = 'ようこそ%s';
$a->strings['Please upload a profile photo.'] = 'プロフィール写真をアップロードしてください。';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: %2$sさん、%1$sへようこそ';
$a->strings['Friendica Notification'] = 'Friendica の通知';
$a->strings['YYYY-MM-DD or MM-DD'] = 'YYYY-MM-DDまたはMM-DD';
$a->strings['less than a second ago'] = '1秒以内前';
$a->strings['year'] = '年';
$a->strings['years'] = '年';
$a->strings['months'] = '月';
$a->strings['weeks'] = '週間';
$a->strings['days'] = '日';
$a->strings['hour'] = '時間';
$a->strings['hours'] = '時間';
$a->strings['minute'] = '分';
$a->strings['minutes'] = '分';
$a->strings['second'] = '秒';
$a->strings['seconds'] = '秒';
$a->strings['%1$d %2$s ago'] = '%1$d%2$s前';
$a->strings['Copy or paste schemestring'] = 'スキーム文字列のコピーまたは貼り付け';
$a->strings['You can copy this string to share your theme with others. Pasting here applies the schemestring'] = 'この文字列をコピーして、テーマを他の人と共有できます。ここに貼り付けると、スキーム文字列が適用されます';
$a->strings['Navigation bar background color'] = 'ナビゲーションバーの背景色';
