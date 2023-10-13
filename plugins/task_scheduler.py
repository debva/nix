import os
import time
import json
import shutil
import requests
import keyboard
from datetime import datetime, timedelta
from base64 import b64encode, b64decode
from tkinter import Tk, Label, Entry, Text, Button, messagebox, END

def clear_terminal():
    if os.name == 'posix':
        os.system('clear')
    elif os.name == 'nt':
        os.system('cls')

def set_terminal_title(title):
    clear_terminal()
    if os.name == 'posix':
        print(f'\033]0;{title}\007', end='', flush=True)
    elif os.name == 'nt':
        os.system(f'title {title}')

def writeFile(filename, content):
    with open(f"{datadir}{separator}{filename}.bin", 'wb') as file:
        file.write(b64encode(json.dumps(content).encode()))

def appendFile(filename, content):
    with open(f"{datadir}{separator}{filename}.txt", 'a') as file:
        file.write(content)

def readAppendFile(filename):
    try:
        with open(f"{datadir}{separator}{filename}.txt", 'r') as file:
            return file.readlines()
    except FileNotFoundError:
        return None

def readFile(filename):
    try:
        with open(f"{datadir}{separator}{filename}.bin", 'rb') as file:
            return json.loads(b64decode(file.read()).decode())
    except FileNotFoundError:
        return {}

def configuration(e):
    if keyboard.is_pressed('ctrl+/'):
        root = Tk()
        root.focus_force()
        root.title("Configuration")

        Label(root, text="Server URL:").pack()
        url = Entry(root, width=50)
        url.pack()

        Label(root, text="Queue ID:").pack()
        queueid = Entry(root, width=50)
        queueid.pack()

        Label(root, text="Interval:").pack()
        interval = Entry(root, width=50)
        interval.pack()

        Label(root, text="Username:").pack()
        username = Entry(root, width=50)
        username.pack()

        Label(root, text="Password:").pack()
        password = Entry(root, width=50)
        password.pack()

        def saveConfig():
            urlValue = url.get()
            if urlValue:
                config = {
                    "url": urlValue.strip('/\\'),
                    "qid": queueid.get() if queueid.get() else 'nix',
                    "interval": interval.get() if interval.get() else 5,
                    "username": username.get(),
                    "password": password.get()
                }
                writeFile('config', config)
                root.destroy()
            else:
                messagebox.showerror("Error", "Servel URL is required!")

        save_button = Button(root, text="Save", width=25, command=saveConfig)
        save_button.pack()

        existingConfig = readFile('config')
        url.insert(0, existingConfig.get('url', ''))
        queueid.insert(0, existingConfig.get('qid', ''))
        interval.insert(0, existingConfig.get('interval', ''))
        username.insert(0, existingConfig.get('username', ''))
        password.insert(0, existingConfig.get('password', ''))

        root.mainloop()

def task(e):
    if keyboard.is_pressed('ctrl+i'):
        root = Tk()
        root.focus_force()
        root.title("Tasks")

        display = Text(root, height=50, width=123)
        display.pack()

        tasks = readFile('task')

        display.delete(1.0, END)  
        display.insert(END, json.dumps(tasks if len(tasks) > 0 else 'No Task', indent=4))

        root.mainloop()

def history(e):
    if keyboard.is_pressed('ctrl+l'):
        root = Tk()
        root.focus_force()
        root.title("Log History")

        display = Text(root, height=50, width=123)
        display.pack()

        history = readAppendFile('history')
        history = history if history is not None else ['No Log History']

        display.delete(1.0, END)  
        for line in history:
            display.insert(END, line.strip() + '\n\n')

        root.mainloop()

def reload(e):
    if keyboard.is_pressed('ctrl+r'):
        global isStartup, isAuthenticated
        isStartup = isAuthenticated = True

def createStatus(prefix = '', isError = False):
    status = f"[\033[91m✕\033[0m]" if isError else f"[\033[32m✓\033[0m]"
    print(f"{prefix}{'_' * (shutil.get_terminal_size().columns - len(prefix) - 3)}{status}", end="", flush=True)

def runTimeTasks(url, tasks, index = 0):
    now = datetime.now()
    nowStr = f"[{now.strftime('%Y-%m-%d %H:%M:%S')}]"

    if len(tasks) > 0:
        task = tasks[0]
        if (task is not None):
            if task <= now:
                try:
                    response = requests.get(url)
                    
                    if response.status_code != 200:
                        global isAuthenticated
                        isAuthenticated = False
                        raise Exception(response.json().get('message', 'Response Error'))

                    writeFile('task', response.json())
                    createStatus(f"[{now.strftime('%Y-%m-%d %H:%M:%S')}]")
                    appendFile('history', f"{nowStr}[OK]\n")
                    
                    index += 1
                    runTimeTasks(url, tasks[1:], index)
                
                except Exception as e:
                    appendFile('history', f"[{nowStr}]{str(e)}\n")
                    createStatus(f"{nowStr}[OK]\n", True)

        else:
            index += 1
            runTimeTasks(url, tasks[1:], index)

datadir = 'data'
separator = os.path.sep
isStartup = isAuthenticated = True

if not os.path.exists(datadir):
    os.makedirs(datadir)

set_terminal_title('Task Scheduler')

keyboard.on_press_key('ctrl', configuration)
keyboard.on_press_key('/', configuration)

keyboard.on_press_key('ctrl', task)
keyboard.on_press_key('i', task)

keyboard.on_press_key('ctrl', history)
keyboard.on_press_key('l', history)

keyboard.on_press_key('ctrl', reload)
keyboard.on_press_key('r', reload)

print(f"(CTRL + / = Configuration | CTRL + I = Information | CTRL + L = Log History | CTRL + R = Reload)\n")

try:
    while True:
        time.sleep(1)

        now = datetime.now()
        nowStr = f"{now.strftime('%Y-%m-%d %H:%M:%S')}"
        config = readFile('config')
        tasks = readFile('tasks')
        
        if (len(config) > 0 and isAuthenticated):
            username = config['username'].strip()
            password = config['password'].strip()
            url = f"{config['url']}/___queue_{config['qid']}?username={username}&password={password}"

            if (isStartup):
                try:
                    recordRuntime = now + timedelta(minutes=int(config['interval']))
                    response = requests.get(url)

                    if response.status_code != 200:
                        isAuthenticated = False
                        raise Exception(response.json().get('message', 'Response Error'))
                    
                    writeFile('task', response.json())
                    createStatus(f"[{nowStr}]")
                    
                    isStartup = False

                    appendFile('history', f"[{nowStr}][OK]\n")
                
                except Exception as e:
                    appendFile('history', f"[{nowStr}]{str(e)}\n")
                    createStatus(f"[{nowStr}]", True)
                    continue

            if (len(tasks) > 0):
                runTask = list(map(lambda s: s['run_at'], tasks))
                if None in runTask:
                    try:
                        recordRuntime = now + timedelta(minutes=int(config['interval']))
                        response = requests.get(url)
                        
                        if response.status_code != 200:
                            isAuthenticated = False
                            raise Exception(response.json().get('message', 'Response Error'))
                        
                        writeFile('task', response.json())
                        createStatus(f"[{nowStr}]")
                        appendFile('history', f"[{nowStr}][OK]\n")
                    
                    except Exception as e:
                        appendFile('history', f"[{nowStr}]{str(e)}\n")
                        createStatus(f"[{nowStr}]", True)
                        continue

                else:
                    recordRuntime = now + timedelta(minutes=int(config['interval']))
                    formattingTimeTask = [datetime.strptime(runAt, '%Y-%m-%d %H:%M:%S') for runAt in runTask]
                    runTimeTasks(url, formattingTimeTask)

            if recordRuntime <= now:
                try:
                    recordRuntime = now + timedelta(minutes=int(config['interval']))
                    response = requests.get(url)
                    
                    if response.status_code != 200:
                        isAuthenticated = False
                        raise Exception(response.json().get('message', 'Response Error'))
                    
                    writeFile('task', response.json())
                    createStatus(f"[{nowStr}]")
                    appendFile('history', f"[{nowStr}][OK]\n")
                
                except Exception as e:
                    appendFile('history', f"[{nowStr}]{str(e)}\n")
                    createStatus(f"[{nowStr}]", True)
                    continue

except KeyboardInterrupt:
    clear_terminal()

finally:
    keyboard.unhook_all()