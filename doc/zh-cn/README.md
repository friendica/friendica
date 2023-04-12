# 关于 Friendica 项目文档

**注意**: 这些文件中的某些链接可能在 Friendica 存储库中不起作用，因为它们应该在已安装的 Friendica 节点上运行。

## 用户和管理员文档

每个 Friendica 节点都有用户和管理员文档的 _当前版本_ 可在 `/help` 位置访问。
文档主要用英语编写，但页面可以翻译，并且一些页面已经有了德语翻译。
如果您想帮助扩展文档或翻译，请在 [Friendica wiki](https://wiki.friendi.ca) 注册一个帐户，该网站维护 [文本信息](https://wiki.friendi.ca/docs)。
文档定期从那里合并到 Friendica 的 _development_ 分支中。

您在文档中使用的图像应位于此目录的 `img` 子目录中。
翻译位于以语言代码命名的子目录中，例如 `de`。
根据所选的界面语言，将应用不同的翻译，或者将使用 `en` 作为后备。

## 开发人员文档

我们在 Friendica 存储库的根目录中提供了一个 [Doxygen](https://www.doxygen.nl/index.html) 的配置文件。
有了它，您应该能够从源代码中提取一些文档。

此外，在 `doc/db` 中还有一些有关数据库结构的文档文件。
