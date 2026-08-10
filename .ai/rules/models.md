---
paths:
  - app/Models/User.php
---

# Models

## Unlink machines when users are deleted
When a User is soft-deleted, machines owned by that user must be unbound by clearing machines.user_id, machines.device_id, and machines.user_subscription_id. Also clear devices.user_id for devices linked through those machines. Restoring a user must not relink old machines.
