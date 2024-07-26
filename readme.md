# MediaWiki extension OnlyAuthorCanEditPage

Расширение для MediaWiki 1.42.1, которое позволяет редактировать авторизованным пользователям только те страницы, которые сами и создали.

Запрещено создавать и редактировать чужие пользовательские страницы в пространстве User (если имя пользователя не совпадает с названием страницы).
Разрешено создавать обычные страницы всем авторизованным пользователям.
Запрещено редактировать чужие созданные обычные страницы пользователей.

Администратор имеет право на редактирование всех страниц.

## Прежде чем включить расширение

Нужен создать таблицу авторов страниц

```sql
CREATE TABLE page_authors (
    id INTEGER UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id INTEGER UNSIGNED NOT NULL,
    author_id INTEGER UNSIGNED NOT NULL,
    FOREIGN KEY (page_id) REFERENCES page(page_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES user(user_id) ON DELETE CASCADE
);
```


