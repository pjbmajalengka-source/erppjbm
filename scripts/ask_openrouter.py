import os
import json
import requests
import sys

# Load .env (Simple manual parser for minimal dependencies)
def load_env(filepath):
    env_vars = {}
    if os.path.exists(filepath):
        with open(filepath, 'r') as f:
            for line in f:
                if '=' in line and not line.startswith('#'):
                    key, value = line.strip().split('=', 1)
                    env_vars[key] = value
    return env_vars

# Configurations
ENV = load_env('.env')
API_KEY = ENV.get('OPENROUTER_API_KEY')
DEFAULT_MODEL = ENV.get('DEFAULT_AI_MODEL', 'anthropic/claude-3.5-sonnet')
API_URL = "https://openrouter.ai/api/v1/chat/completions"

def ask_openrouter(prompt, model=None):
    if not API_KEY:
        print("Error: OPENROUTER_API_KEY not found in .env")
        return None

    headers = {
        "Authorization": f"Bearer {API_KEY}",
        "Content-Type": "application/json",
        "HTTP-Referer": "https://pjb.my.id", # Site URL for ranking
        "X-Title": "ERP-PJBM Assistant"
    }

    data = {
        "model": model or DEFAULT_MODEL,
        "messages": [
            {"role": "user", "content": prompt}
        ]
    }

    try:
        response = requests.post(API_URL, headers=headers, data=json.dumps(data))
        response.raise_for_status()
        result = response.json()
        return result['choices'][0]['message']['content']
    except Exception as e:
        print(f"Error calling OpenRouter: {str(e)}")
        return None

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python ask_openrouter.py \"[PROMPT]\" [MODEL]")
        sys.exit(1)

    user_prompt = sys.argv[1]
    target_model = sys.argv[2] if len(sys.argv) > 2 else DEFAULT_MODEL
    
    print(f"--- Sending prompt to OpenRouter ({target_model}) ---")
    output = ask_openrouter(user_prompt, target_model)
    if output:
        print(output)
