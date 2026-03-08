<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat IA - ChatApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg:        #06060f;
            --bg2:       #0a0a18;
            --glass:     rgba(255,255,255,0.04);
            --glass2:    rgba(255,255,255,0.07);
            --glass3:    rgba(255,255,255,0.11);
            --border:    rgba(255,255,255,0.07);
            --border2:   rgba(255,255,255,0.13);
            --red:       #ff3d5a;
            --red2:      #ff6b82;
            --cyan:      #22d3ee;
            --cyan2:     #67e8f9;
            --purple:    #a855f7;
            --purple2:   #c084fc;
            --violet:    #6d5cf5;
            --pink:      #ec4899;
            --text:      #f0f0ff;
            --muted:     #6b6b8a;
            --muted2:    #4a4a6a;
            --online:    #34d399;
        }

        html, body { height: 100%; overflow: hidden; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); }

        /* ─── DEEP BACKGROUND SCENE ─── */
        .bg-scene {
            position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden;
        }
        .bg-scene::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%,  rgba(109,92,245,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 90%,  rgba(236,72,153,0.15) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 85% 15%,  rgba(34,211,238,0.12) 0%, transparent 55%),
                radial-gradient(ellipse 70% 60% at 10% 85%,  rgba(168,85,247,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 40% 30% at 50% 50%,  rgba(255,61,90,0.05)  0%, transparent 50%),
                linear-gradient(160deg, #06060f 0%, #080814 40%, #060612 100%);
        }
        .bg-scene::after {
            content: '';
            position: absolute; inset: 0;
            background:
                linear-gradient(105deg, transparent 30%, rgba(109,92,245,0.06) 45%, transparent 60%),
                linear-gradient(250deg, transparent 30%, rgba(34,211,238,0.05) 48%, transparent 62%);
            animation: aurora 8s ease-in-out infinite alternate;
        }
        @keyframes aurora {
            0%   { opacity: 0.6; transform: translateX(-2%) translateY(1%); }
            100% { opacity: 1;   transform: translateX(2%)  translateY(-1%); }
        }

        /* Floating orbs */
        .orb {
            position: absolute; border-radius: 50%;
            filter: blur(80px); pointer-events: none;
            animation: orbFloat var(--dur, 12s) ease-in-out infinite alternate var(--delay, 0s);
        }
        @keyframes orbFloat {
            0%   { transform: translate(0,0) scale(1); }
            100% { transform: translate(var(--tx,30px), var(--ty,-20px)) scale(var(--sc,1.08)); }
        }
        .orb-1 { width:420px; height:420px; left:-100px; top:-80px;    background:rgba(109,92,245,0.14); --dur:14s; --tx:40px;  --ty:30px;  --sc:1.1; }
        .orb-2 { width:350px; height:350px; right:-80px; bottom:-60px;  background:rgba(236,72,153,0.13); --dur:11s; --delay:-3s; --tx:-30px; --ty:-25px; --sc:0.92; }
        .orb-3 { width:280px; height:280px; right:15%;   top:10%;       background:rgba(34,211,238,0.10); --dur:16s; --delay:-6s; --tx:20px;  --ty:40px;  --sc:1.05; }
        .orb-4 { width:200px; height:200px; left:30%;    bottom:15%;    background:rgba(168,85,247,0.09); --dur:9s;  --delay:-2s; --tx:-15px; --ty:-30px; --sc:1.12; }

        /* Grain noise */
        .grain {
            position: absolute; inset: -50%; width: 200%; height: 200%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
            opacity: 0.032;
            animation: grainShift 0.4s steps(1) infinite;
        }
        @keyframes grainShift {
            0%  { transform: translate(0,0); }  25% { transform: translate(-2%,-1%); }
            50% { transform: translate(1%,2%); } 75% { transform: translate(2%,-2%); }
        }

        /* Grid lines */
        .grid-lines {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.018) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
        }

        /* ─── LAYOUT ─── */
        .app { display: flex; height: 100vh; position: relative; z-index: 1; }

        /* ═══════════════ SIDEBAR ═══════════════ */
        .sidebar {
            width: 72px; flex-shrink: 0;
            background: rgba(8,8,20,0.75);
            backdrop-filter: blur(28px) saturate(180%);
            -webkit-backdrop-filter: blur(28px) saturate(180%);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            align-items: center; padding: 20px 0; gap: 6px;
        }

        .sidebar-logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #ff3d5a, #ff6b82);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; margin-bottom: 22px;
            box-shadow: 0 0 28px rgba(255,61,90,0.45), 0 4px 12px rgba(0,0,0,0.5);
            position: relative;
        }
        .sidebar-logo::after {
            content: ''; position: absolute; inset: -1px; border-radius: 15px;
            background: linear-gradient(135deg, rgba(255,255,255,0.28), transparent 60%);
            pointer-events: none;
        }

        .sidebar-nav { flex: 1; display: flex; flex-direction: column; gap: 6px; align-items: center; width: 100%; }

        .nav-btn {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; cursor: pointer; border: none;
            background: transparent; color: var(--muted);
            transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1);
            position: relative; text-decoration: none;
        }
        .nav-btn:hover {
            background: var(--glass3); color: var(--text);
            transform: scale(1.08);
            box-shadow: 0 0 0 1px var(--border2), 0 8px 20px rgba(0,0,0,0.35);
        }
        .nav-btn.active-ai {
            background: linear-gradient(135deg, rgba(109,92,245,0.22), rgba(34,211,238,0.12));
            color: var(--cyan);
            box-shadow: 0 0 0 1px rgba(109,92,245,0.35), 0 0 22px rgba(109,92,245,0.2);
        }
        .nav-btn.active-ai::before {
            content: ''; position: absolute; left: -1px;
            width: 3px; height: 20px; border-radius: 0 3px 3px 0;
            background: linear-gradient(180deg, var(--cyan), var(--purple));
            box-shadow: 0 0 10px rgba(34,211,238,0.6);
        }

        .tooltip {
            position: absolute; left: 62px; top: 50%; transform: translateY(-50%);
            background: rgba(8,8,20,0.95); backdrop-filter: blur(12px);
            border: 1px solid var(--border2); color: var(--text);
            font-size: 12px; font-weight: 500; padding: 6px 12px;
            border-radius: 10px; white-space: nowrap;
            pointer-events: none; opacity: 0; transition: opacity 0.2s; z-index: 100;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        .nav-btn:hover .tooltip { opacity: 1; }

        .sidebar-bottom { display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .my-initials-sm {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #ff3d5a, #a855f7);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; color: white;
            box-shadow: 0 0 0 2px rgba(255,61,90,0.4), 0 0 20px rgba(255,61,90,0.25);
            margin-top: 8px; font-family: 'Outfit', sans-serif;
            cursor: pointer; transition: transform 0.2s;
        }
        .my-initials-sm:hover { transform: scale(1.1); }

        /* ═══════════════ CHAT MAIN ═══════════════ */
        .chat-main { flex: 1; display: flex; flex-direction: column; }

        /* Header */
        .chat-header {
            padding: 14px 24px;
            display: flex; align-items: center; gap: 14px;
            background: rgba(8,8,20,0.72);
            backdrop-filter: blur(28px) saturate(180%);
            -webkit-backdrop-filter: blur(28px) saturate(180%);
            border-bottom: 1px solid var(--border);
        }

        .ai-avatar-wrap { position: relative; flex-shrink: 0; }
        .ai-avatar {
            width: 46px; height: 46px; border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--violet), var(--purple));
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; position: relative;
            box-shadow: 0 0 0 2px rgba(34,211,238,0.3), 0 0 28px rgba(109,92,245,0.5), 0 0 60px rgba(34,211,238,0.2);
            animation: aiPulse 3s ease-in-out infinite;
        }
        @keyframes aiPulse {
            0%,100% { box-shadow: 0 0 0 2px rgba(34,211,238,0.3), 0 0 28px rgba(109,92,245,0.5), 0 0 60px rgba(34,211,238,0.2); }
            50%      { box-shadow: 0 0 0 3px rgba(34,211,238,0.5), 0 0 40px rgba(109,92,245,0.7), 0 0 80px rgba(34,211,238,0.3); }
        }
        .ai-avatar::before {
            content: ''; position: absolute; inset: -3px; border-radius: 50%;
            background: conic-gradient(from 0deg, var(--cyan), var(--purple), var(--pink), var(--cyan));
            z-index: -1; animation: spinRing 4s linear infinite; filter: blur(2px);
        }
        @keyframes spinRing { to { transform: rotate(360deg); } }

        .online-dot {
            position: absolute; bottom: 2px; right: 2px;
            width: 13px; height: 13px; border-radius: 50%;
            background: var(--online); border: 2px solid var(--bg2);
            box-shadow: 0 0 8px rgba(52,211,153,0.8);
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100% { opacity:1; } 50% { opacity:0.6; } }

        .chat-info { flex: 1; }
        .chat-name { font-size: 16px; font-weight: 700; font-family: 'Outfit', sans-serif; color: var(--text); letter-spacing: -0.2px; }
        .chat-status { font-size: 12px; color: var(--online); margin-top: 2px; font-weight: 500; }

        .ai-badge {
            padding: 5px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 700;
            font-family: 'Outfit', sans-serif; letter-spacing: 0.5px;
            background: linear-gradient(135deg, rgba(34,211,238,0.15), rgba(109,92,245,0.15));
            border: 1px solid rgba(34,211,238,0.25);
            color: var(--cyan2);
            box-shadow: 0 0 16px rgba(34,211,238,0.15), inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative; overflow: hidden;
        }
        .ai-badge::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent 60%);
        }

        .btn-new-session {
            background: var(--glass2); border: 1px solid var(--border2);
            color: var(--muted); padding: 8px 16px; border-radius: 12px;
            font-size: 12px; font-family: 'DM Sans', sans-serif; cursor: pointer;
            text-decoration: none; display: flex; align-items: center; gap: 6px;
            transition: all 0.25s; backdrop-filter: blur(8px); font-weight: 500;
        }
        .btn-new-session:hover {
            background: linear-gradient(135deg, rgba(109,92,245,0.18), rgba(34,211,238,0.1));
            border-color: rgba(109,92,245,0.4); color: var(--cyan2);
            box-shadow: 0 0 18px rgba(109,92,245,0.2);
        }

        /* Messages */
        .messages-area {
            flex: 1; overflow-y: auto;
            padding: 28px 28px 20px;
            display: flex; flex-direction: column; gap: 10px;
            scroll-behavior: smooth;
        }
        .messages-area::-webkit-scrollbar { width: 3px; }
        .messages-area::-webkit-scrollbar-track { background: transparent; }
        .messages-area::-webkit-scrollbar-thumb { background: rgba(109,92,245,0.3); border-radius: 4px; }

        /* Empty state */
        .empty-state {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center; padding: 40px; gap: 14px;
        }
        .empty-icon-wrap {
            position: relative; width: 90px; height: 90px;
            display: flex; align-items: center; justify-content: center; margin-bottom: 8px;
        }
        .empty-icon-wrap::before {
            content: ''; position: absolute; inset: 0; border-radius: 50%;
            background: conic-gradient(from 0deg, var(--cyan), var(--purple), var(--pink), var(--cyan));
            animation: spinRing 4s linear infinite; filter: blur(3px); opacity: 0.6;
        }
        .empty-icon-inner {
            width: 76px; height: 76px; border-radius: 50%;
            background: linear-gradient(135deg, rgba(109,92,245,0.3), rgba(34,211,238,0.2));
            backdrop-filter: blur(12px); border: 1px solid rgba(34,211,238,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; position: relative; z-index: 1;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.15);
        }
        .empty-title {
            font-size: 22px; font-weight: 700; font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, var(--text), var(--cyan2), var(--purple2));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .empty-sub { font-size: 13px; color: var(--muted); max-width: 300px; line-height: 1.7; }

        .suggestions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 10px; }
        .suggestion {
            background: var(--glass2); border: 1px solid var(--border2);
            color: var(--muted); padding: 9px 16px; border-radius: 22px;
            font-size: 13px; font-family: 'DM Sans', sans-serif; cursor: pointer;
            transition: all 0.25s; backdrop-filter: blur(8px); position: relative; overflow: hidden;
        }
        .suggestion::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.06), transparent 60%);
        }
        .suggestion:hover {
            background: linear-gradient(135deg, rgba(109,92,245,0.2), rgba(34,211,238,0.1));
            border-color: rgba(34,211,238,0.35); color: var(--cyan2);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(109,92,245,0.2), 0 0 0 1px rgba(34,211,238,0.2);
        }

        /* Bubbles */
        .msg-row {
            display: flex; flex-direction: column;
            animation: msgIn 0.3s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes msgIn {
            from { opacity: 0; transform: translateY(12px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }
        .msg-row.mine   { align-items: flex-end; }
        .msg-row.theirs { align-items: flex-start; }

        .msg-bubble {
            max-width: 64%; padding: 12px 17px 9px;
            font-size: 14px; line-height: 1.6; word-wrap: break-word;
            position: relative; transition: transform 0.2s;
        }
        .msg-bubble:hover { transform: translateY(-1px); }

        /* MINE — vivid red-pink gradient glass */
        .msg-row.mine .msg-bubble {
            background: linear-gradient(135deg, rgba(255,61,90,0.88), rgba(236,72,153,0.75));
            backdrop-filter: blur(16px) saturate(200%);
            -webkit-backdrop-filter: blur(16px) saturate(200%);
            border-radius: 22px 22px 5px 22px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 32px rgba(255,61,90,0.3), 0 2px 8px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.28);
            color: white;
        }
        .msg-row.mine .msg-bubble::before {
            content: ''; position: absolute; inset: 0; border-radius: inherit;
            background: linear-gradient(135deg, rgba(255,255,255,0.18) 0%, transparent 55%);
            pointer-events: none;
        }

        /* THEIRS — cyan-purple glass */
        .msg-row.theirs .msg-bubble {
            background: linear-gradient(135deg, rgba(109,92,245,0.18), rgba(34,211,238,0.1));
            backdrop-filter: blur(20px) saturate(200%);
            -webkit-backdrop-filter: blur(20px) saturate(200%);
            border-radius: 22px 22px 22px 5px;
            border: 1px solid rgba(34,211,238,0.2);
            box-shadow: 0 8px 32px rgba(109,92,245,0.18), 0 2px 8px rgba(0,0,0,0.35), inset 0 1px 0 rgba(255,255,255,0.1);
            color: var(--text);
        }
        .msg-row.theirs .msg-bubble::before {
            content: ''; position: absolute; inset: 0; border-radius: inherit;
            background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, transparent 55%);
            pointer-events: none;
        }

        .msg-meta { display: flex; align-items: center; gap: 4px; justify-content: flex-end; margin-top: 5px; }
        .msg-time { font-size: 10px; color: rgba(255,255,255,0.4); }
        .msg-row.theirs .msg-time { color: var(--muted2); }

        /* Typing */
        .typing-row { display: none; align-items: flex-start; }
        .typing-bubble {
            padding: 14px 18px;
            background: linear-gradient(135deg, rgba(109,92,245,0.18), rgba(34,211,238,0.1));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(34,211,238,0.2);
            border-radius: 22px 22px 22px 5px;
            box-shadow: 0 8px 24px rgba(109,92,245,0.15), inset 0 1px 0 rgba(255,255,255,0.1);
        }
        .dots { display: flex; gap: 5px; align-items: center; }
        .dots span {
            width: 7px; height: 7px; border-radius: 50%;
            background: linear-gradient(135deg, var(--cyan), var(--purple));
            animation: dot 1.2s ease-in-out infinite;
            box-shadow: 0 0 6px rgba(34,211,238,0.5);
        }
        .dots span:nth-child(2) { animation-delay: 0.2s; }
        .dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dot {
            0%,60%,100% { transform: translateY(0); opacity: 0.4; }
            30%          { transform: translateY(-7px); opacity: 1; }
        }

        /* Input area */
        .input-area {
            padding: 14px 20px;
            display: flex; align-items: flex-end; gap: 10px;
            background: rgba(8,8,20,0.75);
            backdrop-filter: blur(28px) saturate(180%);
            -webkit-backdrop-filter: blur(28px) saturate(180%);
            border-top: 1px solid var(--border);
        }
        .msg-input {
            flex: 1; padding: 13px 20px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border2);
            border-radius: 24px;
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            color: var(--text); outline: none; resize: none;
            max-height: 120px; line-height: 1.5;
            transition: all 0.25s; backdrop-filter: blur(8px);
        }
        .msg-input::placeholder { color: var(--muted2); }
        .msg-input:focus {
            background: rgba(255,255,255,0.07);
            border-color: rgba(34,211,238,0.35);
            box-shadow: 0 0 0 3px rgba(34,211,238,0.08), 0 0 20px rgba(109,92,245,0.1);
        }

        /* Send button — iridescent */
        .send-btn {
            width: 50px; height: 50px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--cyan), var(--violet), var(--purple));
            border: none; color: white; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 24px rgba(34,211,238,0.35), 0 0 48px rgba(109,92,245,0.2), 0 4px 16px rgba(0,0,0,0.4);
            transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1);
            position: relative; overflow: hidden;
        }
        .send-btn::before {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.28), transparent 60%);
            pointer-events: none;
        }
        .send-btn:hover {
            transform: scale(1.12);
            box-shadow: 0 0 32px rgba(34,211,238,0.5), 0 0 64px rgba(109,92,245,0.35), 0 6px 24px rgba(0,0,0,0.5);
        }
        .send-btn:active { transform: scale(0.95); }
        .send-btn:disabled { background: var(--glass2); box-shadow: none; cursor: not-allowed; transform: none; }

        .input-hint {
            text-align: center; font-size: 11px; color: var(--muted2);
            padding: 5px 0 10px;
            background: rgba(8,8,20,0.75); backdrop-filter: blur(28px);
            letter-spacing: 0.2px;
        }
        .input-hint span {
            background: linear-gradient(90deg, var(--cyan2), var(--purple2));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; font-weight: 600;
        }
    </style>
</head>
<body>

<!-- Deep background scene -->
<div class="bg-scene">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="orb orb-4"></div>
    <div class="grid-lines"></div>
    <div class="grain"></div>
</div>

<div class="app">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">💬</div>
        <div class="sidebar-nav">
            <a href="{{ route('chat.index') }}" class="nav-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <span class="tooltip">Volver al chat</span>
            </a>
            <a href="{{ route('ai-chat.index') }}" class="nav-btn active-ai">
                🤖
                <span class="tooltip">Chat con IA</span>
            </a>
        </div>
        <div class="sidebar-bottom">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span class="tooltip">Cerrar sesión</span>
                </button>
            </form>
            @php $authUser = Auth::user(); @endphp
            <div class="my-initials-sm">{{ strtoupper(substr($authUser->name, 0, 1)) }}</div>
        </div>
    </div>

    <!-- CHAT MAIN -->
    <div class="chat-main">

        <!-- Header -->
        <div class="chat-header">
            <div class="ai-avatar-wrap">
                <div class="ai-avatar">🤖</div>
                <div class="online-dot"></div>
            </div>
            <div class="chat-info">
                <div class="chat-name">Asistente IA</div>
                <div class="chat-status">● Siempre disponible</div>
            </div>
            <span class="ai-badge">⚡ Llama 3.3</span>
            <a href="{{ route('ai-chat.new') }}" class="btn-new-session" style="margin-left:10px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nueva sesión
            </a>
        </div>

        <!-- Messages -->
        <div class="messages-area" id="messagesArea">

            @if($messages->isEmpty())
            <div class="empty-state" id="emptyState">
                <div class="empty-icon-wrap">
                    <div class="empty-icon-inner">🤖</div>
                </div>
                <div class="empty-title">¡Hola, {{ $authUser->name }}!</div>
                <p class="empty-sub">Soy tu asistente de IA con Llama 3.3. Puedo ayudarte con preguntas, redacción, código, traducciones y mucho más.</p>
                <div class="suggestions">
                    <button class="suggestion" onclick="useSuggestion('💡 Dame una idea creativa para un proyecto')">💡 Idea creativa</button>
                    <button class="suggestion" onclick="useSuggestion('📝 Ayúdame a redactar un correo profesional')">📝 Redactar texto</button>
                    <button class="suggestion" onclick="useSuggestion('💻 Explícame qué es una API REST')">💻 Concepto técnico</button>
                    <button class="suggestion" onclick="useSuggestion('🌍 Traduce Hello how are you al español')">🌍 Traducir algo</button>
                </div>
            </div>
            @else
                @foreach($messages as $msg)
                <div class="msg-row {{ $msg->role === 'user' ? 'mine' : 'theirs' }}">
                    <div class="msg-bubble">
                        {{ $msg->content }}
                        <div class="msg-meta">
                            <span class="msg-time">{{ $msg->created_at->format('H:i') }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            @endif

            <!-- Typing indicator -->
            <div class="typing-row" id="typingRow">
                <div class="typing-bubble">
                    <div class="dots"><span></span><span></span><span></span></div>
                </div>
            </div>
        </div>

        <!-- Input -->
        <div class="input-area">
            <textarea class="msg-input" id="msgInput" placeholder="Escribe un mensaje a la IA..." rows="1"></textarea>
            <button class="send-btn" id="sendBtn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </div>
        <div class="input-hint">Powered by <span>Groq · Llama 3.3 70B</span> · Gratis 🇲🇽</div>
    </div>
</div>

<input type="hidden" id="sessionId" value="{{ $sessionId }}">

<script>
    const area      = document.getElementById('messagesArea');
    const input     = document.getElementById('msgInput');
    const sendBtn   = document.getElementById('sendBtn');
    const typing    = document.getElementById('typingRow');
    const sessionId = document.getElementById('sessionId').value;

    const scrollBottom = () => { area.scrollTop = area.scrollHeight; };
    scrollBottom();

    input.addEventListener('input', function(){
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    sendBtn.addEventListener('click', sendMessage);

    function useSuggestion(text) { input.value = text; input.focus(); }
    function esc(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    function nowTime() { return new Date().toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' }); }

    function addBubble(role, text) {
        const empty = document.getElementById('emptyState');
        if (empty) empty.remove();
        const div = document.createElement('div');
        div.className = `msg-row ${role === 'user' ? 'mine' : 'theirs'}`;
        div.innerHTML = `
            <div class="msg-bubble">
                ${esc(text).replace(/\n/g, '<br>')}
                <div class="msg-meta"><span class="msg-time">${nowTime()}</span></div>
            </div>`;
        area.insertBefore(div, typing);
        scrollBottom();
    }

    async function sendMessage() {
        const text = input.value.trim();
        if (!text || sendBtn.disabled) return;
        addBubble('user', text);
        input.value = ''; input.style.height = 'auto';
        sendBtn.disabled = true;
        typing.style.display = 'flex';
        scrollBottom();
        try {
            const res = await fetch('{{ route("ai-chat.send") }}', {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ message: text, session_id: sessionId }),
            });
            const data = await res.json();
            typing.style.display = 'none';
            addBubble('assistant', data.error ? '❌ ' + data.error : data.message);
        } catch (err) {
            typing.style.display = 'none';
            addBubble('assistant', '❌ Error de conexión. Intenta de nuevo.');
        }
        sendBtn.disabled = false;
        input.focus();
    }
</script>
</body>
</html>