# Upkeepify QNAP Polling Deployer Setup

This directory contains a Docker-based polling deployer that automatically pulls the latest Upkeepify plugin code from GitHub when CI checks pass.

## How It Works

1. **GitHub Actions** (cloud): runs tests, linting, SAST, and dependency checks on every push
2. **Polling Container** (QNAP): every 5 minutes, checks GitHub API for the latest commit status
3. **Auto-Deploy**: if all checks passed and SHA differs from deployed version, pulls code and syncs to the WordPress plugin folder

## Setup on QNAP

### 1. Copy Files to QNAP

Copy the `deploy/` folder to your QNAP at `/docker-data/wordpress-dev/deployer/`:

```bash
scp -r deploy/ anthony@192.168.0.247:/docker-data/wordpress-dev/deployer/
```

Or manually copy:
- `Dockerfile`
- `poll.sh`

### 2. Update docker-compose.yml

Edit `/docker-data/wordpress-dev/docker-compose.yml` and add this service:

```yaml
  upkeepify-deployer:
    build: ./deployer
    container_name: upkeepify_deployer
    volumes:
      - /docker-data/wordpress-dev/wordpress/wp-content/plugins/upkeepify:/plugin
    environment:
      POLL_INTERVAL: 300          # Check every 5 minutes
      REPO: anthonyhorne/upkeepify
      BRANCH: main
    restart: always
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

### 3. Start the Deployer

```bash
cd /docker-data/wordpress-dev
docker-compose up -d upkeepify-deployer
```

Or rebuild and start:

```bash
docker-compose up -d --build upkeepify-deployer
```

### 4. Verify It's Working

Check logs:

```bash
docker logs upkeepify_deployer
```

You should see output like:

```
[2026-03-19 10:15:30] Starting Upkeepify polling deployer
[2026-03-19 10:15:30] Repository: anthonyhorne/upkeepify
[2026-03-19 10:15:30] Branch: main
[2026-03-19 10:15:30] Plugin directory: /plugin
[2026-03-19 10:15:30] Poll interval: 300s
[2026-03-19 10:15:31] Checking for updates...
[2026-03-19 10:15:31] Already at abc123... — no update needed
```

## Testing the Deployment

1. Make a commit to GitHub main branch
2. Watch GitHub Actions run the CI workflow
3. Once all checks pass, the deployer will pull the code
4. Check QNAP logs: `docker logs upkeepify_deployer -f`
5. Verify files updated: `ls -la /docker-data/wordpress-dev/wordpress/wp-content/plugins/upkeepify/`

## Stopping / Restarting

```bash
# Stop
docker-compose stop upkeepify-deployer

# Start
docker-compose start upkeepify-deployer

# Restart
docker-compose restart upkeepify-deployer

# Rebuild and restart
docker-compose up -d --build upkeepify-deployer
```

## Environment Variables

You can customize the deployer by setting environment variables in `docker-compose.yml`:

- `POLL_INTERVAL`: seconds between GitHub API polls (default: 300 / 5 minutes)
- `REPO`: GitHub repository (default: anthonyhorne/upkeepify)
- `BRANCH`: Git branch to track (default: main)
- `PLUGIN_DIR`: path to plugin folder inside container (default: /plugin)

## Notes

- The deployer runs in Alpine Linux (lightweight, ~5MB)
- It uses GitHub's public API (60 requests/hour unauthenticated, no auth token needed)
- A `.deployed-sha` file tracks the currently deployed commit SHA
- If all checks fail, the deployer skips deployment and waits for the next cycle
- Deploy folder is excluded from deployment (only plugin code is synced)

## Troubleshooting

### Deployer not updating plugin

1. Check logs: `docker logs upkeepify_deployer`
2. Verify GitHub Actions passed: check `github.com/anthonyhorne/upkeepify/actions`
3. Verify volume mount: `docker inspect upkeepify_deployer | grep -A 5 Mounts`
4. Test API manually: `curl https://api.github.com/repos/anthonyhorne/upkeepify/branches/main`

### Permission denied writing to plugin folder

The plugin folder permissions may need adjustment:

```bash
chmod -R 755 /docker-data/wordpress-dev/wordpress/wp-content/plugins/upkeepify
```

Or change container to run as a specific user (add to docker-compose.yml):

```yaml
upkeepify-deployer:
  ...
  user: "33:33"  # www-data user in WordPress container
```

### API rate limit

If you hit the 60-request/hour limit, provide a GitHub token in docker-compose.yml:

```yaml
environment:
  GITHUB_TOKEN: ghp_xxxxx...
```

(Not needed for public repos unless you have very frequent polls)
