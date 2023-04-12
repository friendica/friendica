Friendica消息流程
===============

本页面记录了Friendica网络中消息从一个人到另一个人的一些细节。使用多个协议和消息格式有多条路径。

那些希望理解这些消息流程的人应该至少熟悉[DFRN协议文档](https://github.com/friendica/friendica/blob/stable/spec/dfrn2.pdf)和OStatus堆栈的消息传递元素（salmon和Pubsubhubbub）。

当发布一条消息时，所有直接发送到所有网络的送货都使用include/notifier.php完成，该文件选择如何以及向谁发送消息。该文件还调用包括DFRN-notify在内的所有交付的本地方面。

mod/dfrn_notify.php处理DFRN-notify的远程方面。

mod/dfrn_poll.php生成本地feed，并处理DFRN-poll协议的远程方面。

Salmon通知通过mod/salmon.php到达。

推送（pubsubhubbub）源通过mod/pubsub.php到达

DFRN-poll源导入通过src/Worker/OnePoll.php作为计划任务到达，它实现了DFRN-poll协议的本地方面。

### 场景1：Bob发布一条公共状态消息

这是一条没有会话成员的公共消息，因此不使用私人传输。它有两条路径 - 作为bbcode路径传递到DFRN客户端，并转换为HTML与服务器的PuSH（pubsubhubbub）中心通知。当一个PuSH中心运行时，dfrn-poll客户端更喜欢通过PuSH通道获得它们的信息。如果使用默认的Google参考中心，则它们将在出现交付问题时回退到每日轮询。如果没有指定的中心或中心，DFRN客户端将以可配置的速率（每联系人）轮询，最高达5分钟的间隔。通过dfrn-poll检索的源是bbcode，并且还可能包含工作人员有权限查看的私人对话。

### 场景2：Jack回复Bob的公共消息。Jack在Friendica/DFRN网络上。

Jack使用DFRN-notify向Bob发送直接回复。然后，Bob创建一条会话的源，并使用DFRN-notify将其发送给所有参与会话的人。PuSH中心会被通知新内容可用。然后，中心或中心将检索最新源并将其传输到所有中心订阅者（可能在不同的网络上）。

### 场景3：Mary回复Bob的公共消息。Mary在Friendica/DFRN网络上。

Mary使用DFRN-notify向Bob发送直接回复。然后，Bob创建一条会话的源，并将其发送给所有参与会话的人（不包括自己，会话现在发送给Jack和Mary）。消息使用DFRN-notify发送。也通知了Push中心，新内容可用。然后，中心或中心将检索最新源并将其传输到所有中心订阅者（可能在不同的网络上）。

### 场景4：William回复Bob的公共消息。William在OStatus网络上。

William使用salmon通知Bob回复。内容是嵌入在salmon魔术信封中的html。然后，Bob创建一条会话的源，并使用DFRN-notify将其发送给所有Friendica参与者（不包括自己，会话发送给Jack和Mary）。Push中心会被通知新内容可用。然后，中心或中心将检索最新源并将其传输到所有中心订阅者（可能在不同的网络上）。

### 场景5：Bob向Mary和Jack发布私人消息。

消息立即通过DFRN-notify交付给Mary和Jack。公共中心不会收到通知。尝试重新排队以防超时。回复遵循公共回复相同的流程，除了不会通知中心，消息也永远不会在公共源中提供。整个对话也通过他们的dfrn-poll个性化源提供给Mary和Jack（没有其他人可以看到）。
