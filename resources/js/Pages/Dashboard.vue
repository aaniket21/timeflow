<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isDark = ref(true);

const toggleMode = () => {
    isDark.value = !isDark.value;
};

const sendPrompt = (promptText) => {
    console.log("Prompt:", promptText);
};

const timerText = ref('01:24:41');
let s = 5081;
let interval = null;

onMounted(() => {
    interval = setInterval(() => {
        s++;
        const h = String(Math.floor(s / 3600)).padStart(2, '0');
        const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
        const sc = String(s % 60).padStart(2, '0');
        timerText.value = `${h}:${m}:${sc}`;
    }, 1000);
});

onUnmounted(() => {
    if (interval) clearInterval(interval);
});

const plans = ref([
    { id: 0, text: 'Complete React component library', done: true },
    { id: 1, text: 'Revise DSA chapter 7 — trees & graphs', done: false },
    { id: 2, text: '30-min gym session before dinner', done: false }
]);

const togglePlan = (id) => {
    const plan = plans.value.find(p => p.id === id);
    if (plan) plan.done = !plan.done;
};

const exerciseHabitDone = ref(false);
const toggleExerciseHabit = () => {
    exerciseHabitDone.value = !exerciseHabitDone.value;
};

const heatmapData = [
  [0,1,2,3,2,1],[2,4,3,4,1,0],[1,3,4,3,2,1],[0,2,3,4,3,2],
  [1,2,4,3,1,0],[3,4,3,2,1,0],[4,3,2,1,0,0],[2,3,4,3,2,1],
  [0,1,2,3,4,2],[1,2,3,4,3,1],[2,3,4,3,2,0],[1,2,3,4,3,1],
  [0,1,2,3,2,1],[3,4,3,2,1,0]
];
</script>

<template>
  <div :class="[isDark ? 'dark' : 'light']" id="wrapper">
    <div class="mtoggle">
      <span class="mlbl">dark</span>
      <div class="mtrack" @click="toggleMode"><div class="mthumb"></div></div>
      <span class="mlbl">light</span>
    </div>
    <div class="shell">

      <!-- TOPBAR -->
      <header class="topbar">
        <div class="logo"><div class="logo-orb"><i class="ti ti-clock" aria-hidden="true"></i></div>TimeFlow</div>
        <div class="tbr">
          <div class="xp-chip"><i class="ti ti-bolt" aria-hidden="true"></i> 1,240 XP</div>
          <div style="width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;position:relative;" class="t2">
            <i class="ti ti-bell" aria-hidden="true"></i>
            <div style="position:absolute;top:4px;right:4px;width:5px;height:5px;border-radius:50%;background:#F06292;"></div>
          </div>
          <div class="avi">AK</div>
        </div>
      </header>

      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="nav-sec">Main</div>
        <div class="ni on"><i class="ti ti-layout-dashboard" style="font-size:14px;" aria-hidden="true"></i> Dashboard</div>
        <div class="ni t2"><i class="ti ti-player-play" style="font-size:14px;" aria-hidden="true"></i> Timer</div>
        <div class="ni t2"><i class="ti ti-chart-bar" style="font-size:14px;" aria-hidden="true"></i> Analytics</div>
        <div class="ni t2"><i class="ti ti-folder" style="font-size:14px;" aria-hidden="true"></i> Projects<span class="nb">4</span></div>
        <div class="nav-sec">Grow</div>
        <div class="ni t2"><i class="ti ti-trophy" style="font-size:14px;" aria-hidden="true"></i> Achievements</div>
        <div class="ni t2"><i class="ti ti-target" style="font-size:14px;" aria-hidden="true"></i> Goals</div>
        <div class="ni t2"><i class="ti ti-podium" style="font-size:14px;" aria-hidden="true"></i> Leaderboard</div>
        <div class="nav-sec">Export</div>
        <div class="ni t2"><i class="ti ti-file-analytics" style="font-size:14px;" aria-hidden="true"></i> Reports</div>

        <div class="streak-box">
          <span class="sf">🔥</span>
          <div class="sn">14</div>
          <div class="sl">day streak</div>
          <div class="wdots">
            <div class="wd on"></div><div class="wd on"></div><div class="wd on"></div>
            <div class="wd on"></div><div class="wd on"></div><div class="wd on"></div>
            <div class="wd now"></div>
          </div>
        </div>
      </aside>

      <!-- MAIN -->
      <main class="main">

        <!-- Header -->
        <div class="ph fi fd1">
          <div class="greeting t1">Good morning, Arjun 👋</div>
          <div class="dtag t2">Thu, May 14</div>
        </div>

        <!-- Exam countdown chips -->
        <div class="fi fd2">
          <div class="slb">Upcoming exams</div>
          <div class="echips">
            <div class="ec ec-calm"><i class="ti ti-school" style="font-size:11px;" aria-hidden="true"></i> Physics · 18 days</div>
            <div class="ec ec-warn"><i class="ti ti-alert-triangle" style="font-size:11px;" aria-hidden="true"></i> Maths · 9 days</div>
            <div class="ec ec-hot"><i class="ti ti-alert-circle" style="font-size:11px;" aria-hidden="true"></i> DSA · 3 days</div>
            <div class="ec ec-add" @click="sendPrompt('Add a new upcoming exam')"><i class="ti ti-plus" style="font-size:11px;" aria-hidden="true"></i> Add exam</div>
          </div>
        </div>

        <!-- Timetable strip -->
        <div class="fi fd2">
          <div class="slb">Today's schedule</div>
          <div class="ttstrip">
            <div class="ttb" style="border-color:rgba(239,68,68,.22);background:rgba(239,68,68,.07);">
              <div class="ttb-time" style="color:#EF4444;">8:00 AM</div>
              <div class="ttb-name" style="color:#EF4444;">Physics</div>
              <div class="ttb-type" style="color:rgba(239,68,68,.7);">🎓 class</div>
            </div>
            <div class="ttb" style="border-color:rgba(124,92,252,.25);background:rgba(124,92,252,.08);">
              <div class="ttb-time" style="color:var(--v);">10:00 AM</div>
              <div class="ttb-name" style="color:#A78BFA;">DSA study</div>
              <div class="ttb-type" style="color:rgba(124,92,252,.65);">📚 study</div>
            </div>
            <div class="ttb act" style="border-color:rgba(14,207,164,.38);background:rgba(14,207,164,.09);">
              <div class="row" style="gap:3px;"><div class="pdot"></div><div class="ttb-time" style="color:var(--m);">12:00 PM</div></div>
              <div class="ttb-name" style="color:var(--m);">Frontend dev</div>
              <div class="ttb-type" style="color:rgba(14,207,164,.7);">📚 now</div>
            </div>
            <div class="ttb" style="border-color:rgba(245,166,35,.2);background:rgba(245,166,35,.06);">
              <div class="ttb-time" style="color:var(--a);">2:00 PM</div>
              <div class="ttb-name" style="color:var(--a);">Lunch break</div>
              <div class="ttb-type" style="color:rgba(245,166,35,.65);">☕ break</div>
            </div>
            <div class="ttb" style="border-color:rgba(56,189,248,.2);background:rgba(56,189,248,.06);">
              <div class="ttb-time" style="color:var(--sky);">3:00 PM</div>
              <div class="ttb-name" style="color:var(--sky);">Maths review</div>
              <div class="ttb-type" style="color:rgba(56,189,248,.65);">📚 study</div>
            </div>
            <div class="ttb" style="border-color:rgba(240,98,146,.2);background:rgba(240,98,146,.07);">
              <div class="ttb-time" style="color:var(--r);">6:00 PM</div>
              <div class="ttb-name" style="color:var(--r);">Gym</div>
              <div class="ttb-type" style="color:rgba(240,98,146,.65);">🏃 personal</div>
            </div>
          </div>
        </div>

        <!-- Focus ring + timer -->
        <div class="grid2 fi fd3">
          <div class="card">
            <div class="slb" style="margin-bottom:10px;">Today's focus</div>
            <div class="row" style="gap:11px;">
              <div class="focus-ring-wrap">
                <svg class="ring-svg" width="72" height="72" viewBox="0 0 72 72" aria-hidden="true">
                  <circle class="rtrack" cx="36" cy="36" r="30"/>
                  <circle class="rfill" cx="36" cy="36" r="30"/>
                </svg>
                <div class="rc"><div class="rh t1">3.8h</div><div class="rl t3">of 6h</div></div>
              </div>
              <div>
                <div style="font-size:20px;font-weight:800;letter-spacing:-.5px;color:var(--v);">63%</div>
                <div class="t2" style="font-size:10px;margin-top:3px;">daily goal</div>
                <div class="row" style="gap:10px;margin-top:7px;">
                  <div><div style="font-family:var(--mono);font-size:13px;font-weight:600;color:var(--v);">6</div><div class="t3" style="font-size:9px;">sessions</div></div>
                  <div><div style="font-family:var(--mono);font-size:13px;font-weight:600;color:var(--m);">84</div><div class="t3" style="font-size:9px;">score</div></div>
                  <div><div style="font-family:var(--mono);font-size:13px;font-weight:600;color:var(--a);">38m</div><div class="t3" style="font-size:9px;">avg</div></div>
                </div>
              </div>
            </div>
          </div>
          <div class="timer-card">
            <div class="row between">
              <div class="live-tag"><div class="pdot"></div>LIVE</div>
              <button class="stop-btn" @click="sendPrompt('Stop current timer and show session summary')">■ Stop</button>
            </div>
            <div class="tproj">Coding · Frontend</div>
            <div class="tdig t1">{{ timerText }}</div>
            <div class="tbar"><div class="tbar-f"></div></div>
          </div>
        </div>

        <!-- Today's plan -->
        <div class="card fi fd4">
          <div class="row between" style="margin-bottom:10px;">
            <div class="slb" style="margin-bottom:0;">Today's 3 priorities</div>
            <div class="xpbadge"><i class="ti ti-bolt" style="font-size:10px;" aria-hidden="true"></i>+30 XP on completion</div>
          </div>
          
          <div v-for="plan in plans" :key="plan.id" class="plan-item" @click="togglePlan(plan.id)">
            <div class="chk" :class="{ 'dn': plan.done }">
              <i v-if="plan.done" class="ti ti-check" style="font-size:10px;color:#fff;" aria-hidden="true"></i>
            </div>
            <span class="pt t1" :class="{ 'dn': plan.done }">{{ plan.text }}</span>
          </div>

        </div>

        <!-- Stats row -->
        <div class="stats3 fi fd4">
          <div class="sc">
            <div class="sc-lbl">Today</div>
            <div class="sc-val t1">3.8h</div>
            <div class="sc-sub t2" style="font-size:10px;">logged so far</div>
            <div class="chip-up"><i class="ti ti-arrow-up" style="font-size:9px;" aria-hidden="true"></i>+0.6h vs yesterday</div>
          </div>
          <div class="sc">
            <div class="sc-lbl">This week</div>
            <div class="sc-val t1">24.1h</div>
            <div class="sc-sub t2" style="font-size:10px;">of 40h goal</div>
            <div class="chip-up"><i class="ti ti-arrow-up" style="font-size:9px;" aria-hidden="true"></i>60% complete</div>
          </div>
          <div class="sc">
            <div class="sc-lbl">Streak</div>
            <div class="sc-val" style="color:var(--a);">14d</div>
            <div class="sc-sub t2" style="font-size:10px;">best: 21 days</div>
            <div class="chip-str">2× multiplier</div>
          </div>
        </div>

        <!-- XP + challenge -->
        <div class="grid2 fi fd5">
          <div class="xp-card">
            <div class="lvl-row">
              <div class="lvl-name t1">Dedicated</div>
              <div class="lvl-badge">Lv. 4</div>
            </div>
            <div class="xptrack"><div class="xpfill"></div></div>
            <div class="xp-nums t3"><span>1,240 XP</span><span>1,600 next</span></div>
          </div>
          <div class="ch-card">
            <div class="ch-lbl"><i class="ti ti-bolt" style="font-size:10px;" aria-hidden="true"></i>Daily challenge</div>
            <div class="ch-title t1">Complete 4 Pomodoros without stopping</div>
            <div class="row">
              <div class="ch-dots">
                <div class="cd dn"></div><div class="cd dn"></div><div class="cd dn"></div><div class="cd"></div>
              </div>
              <div class="ch-xp">+50 XP</div>
            </div>
          </div>
        </div>

        <!-- Habits -->
        <div class="card fi fd6">
          <div class="row between" style="margin-bottom:9px;">
            <div class="slb" style="margin-bottom:0;">Habits today</div>
            <div style="font-size:10px;font-weight:600;" class="t2">4 / 5 done</div>
          </div>
          <div class="hrow">
            <span class="hname t1" style="font-size:11px;">📚 Study 2h+</span>
            <div class="hdots2">
              <div class="hc dn" style="background:var(--v);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc dn" style="background:var(--v);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc dn" style="background:var(--v);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc dn" style="background:var(--v);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc dn" style="background:var(--v);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc t3" style="font-size:9px;">S</div>
              <div class="hc t3" style="font-size:9px;">S</div>
            </div>
            <span class="hstreak">🔥 12</span>
          </div>
          <div class="hrow">
            <span class="hname t1" style="font-size:11px;">🏃 Exercise</span>
            <div class="hdots2">
              <div class="hc dn" style="background:var(--r);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc"></div>
              <div class="hc dn" style="background:var(--r);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc" :class="{ 'dn': exerciseHabitDone }" :style="exerciseHabitDone ? { background: 'var(--r)' } : {}" @click="toggleExerciseHabit">
                <i v-if="exerciseHabitDone" class="ti ti-check" style="font-size:9px" aria-hidden="true"></i>
              </div>
              <div class="hc"></div>
              <div class="hc t3" style="font-size:9px;">S</div>
              <div class="hc t3" style="font-size:9px;">S</div>
            </div>
            <span class="hstreak">🔥 4</span>
          </div>
          <div class="hrow">
            <span class="hname t1" style="font-size:11px;">🧘 Meditate</span>
            <div class="hdots2">
              <div class="hc dn" style="background:var(--m);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc dn" style="background:var(--m);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc dn" style="background:var(--m);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc dn" style="background:var(--m);"><i class="ti ti-check" style="font-size:9px;" aria-hidden="true"></i></div>
              <div class="hc"></div>
              <div class="hc t3" style="font-size:9px;">S</div>
              <div class="hc t3" style="font-size:9px;">S</div>
            </div>
            <span class="hstreak">🔥 21</span>
          </div>
        </div>

        <!-- Quick start -->
        <div class="fi fd7">
          <div class="slb">Quick start</div>
          <div class="qrow">
            <button class="qb" @click="sendPrompt('Start a new Pomodoro session')"><div class="qi">🍅</div><div class="ql">Pomodoro</div></button>
            <button class="qb" @click="sendPrompt('Show my weekly analytics breakdown')"><div class="qi">📊</div><div class="ql">Analytics</div></button>
            <button class="qb" @click="sendPrompt('Generate a PDF report for this week')"><div class="qi">📄</div><div class="ql">Report</div></button>
          </div>
        </div>

        <!-- Heatmap -->
        <div class="hmap fi fd8">
          <div class="hmap-head">
            <div style="font-size:12px;font-weight:700;" class="t1">Activity — May 2026</div>
            <div class="row" style="gap:3px;font-size:9.5px;" class="t3">
              Low
              <div class="hcell l1" style="display:inline-block;"></div>
              <div class="hcell l2" style="display:inline-block;"></div>
              <div class="hcell l4" style="display:inline-block;"></div>
              High
            </div>
          </div>
          <div class="hmap-grid">
            <div v-for="(col, ci) in heatmapData" :key="ci" class="hmap-col">
              <div 
                v-for="(val, ri) in col" 
                :key="ri" 
                class="hcell" 
                :class="['l' + val, (ci === 13 && ri === 0) ? 'tdy' : '']"
              ></div>
            </div>
          </div>
        </div>

        <!-- Recent sessions -->
        <div class="scard fi fd9">
          <div class="row between" style="margin-bottom:10px;">
            <div style="font-size:12px;font-weight:700;" class="t1">Recent sessions</div>
            <div class="see-all" @click="sendPrompt('Show all sessions for today')">See all →</div>
          </div>
          <div class="srow">
            <div class="sdot" style="background:var(--v);"></div>
            <div class="sinfo"><div class="sn t1">Frontend work</div><div class="sm t3">Coding · ongoing</div></div>
            <div class="sdur">01:24</div>
          </div>
          <div class="srow">
            <div class="sdot" style="background:var(--m);"></div>
            <div class="sinfo"><div class="sn t1">DSA practice</div><div class="sm t3">Study · 11:20 AM</div></div>
            <div class="sdur">52m</div>
          </div>
          <div class="srow">
            <div class="sdot" style="background:var(--a);"></div>
            <div class="sinfo"><div class="sn t1">Team standup</div><div class="sm t3">Meetings · 9:30 AM</div></div>
            <div class="sdur">15m</div>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>

<style>
/* Scoping is removed because some child elements might need access to it via root, but we encapsulate within wrapper */
#wrapper {
  --sans:'Plus Jakarta Sans',sans-serif;
  --mono:'JetBrains Mono',monospace;
  --v:#7C5CFC;--vs:rgba(124,92,252,0.12);--vb:rgba(124,92,252,0.22);
  --m:#0ECFA4;--ms:rgba(14,207,164,0.12);--mb:rgba(14,207,164,0.25);
  --a:#F5A623;--as:rgba(245,166,35,0.12);--ab:rgba(245,166,35,0.28);
  --r:#F06292;--rs:rgba(240,98,146,0.1);
  --sky:#38BDF8;--skys:rgba(56,189,248,0.1);--skyb:rgba(56,189,248,0.28);
  --red:#EF4444;--reds:rgba(239,68,68,0.1);--redb:rgba(239,68,68,0.28);
  font-family:var(--sans);background:transparent;
  min-height: 100vh;
}

#wrapper * {
  box-sizing: border-box;
}

#wrapper .shell{display:grid;grid-template-columns:190px 1fr;grid-template-rows:auto 1fr;min-height:700px;border-radius:14px;overflow:hidden;transition:all 0.35s;}

#wrapper.dark .shell{background:#0C0C10;border:1px solid rgba(255,255,255,0.1);}
#wrapper.light .shell{background:#F5F0E8;border:1px solid rgba(80,60,20,0.15);}

/* TOPBAR */
#wrapper .topbar{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;padding:11px 18px;transition:all 0.35s;}
#wrapper.dark .topbar{background:#13131A;border-bottom:1px solid rgba(255,255,255,0.07);}
#wrapper.light .topbar{background:#F0EAE0;border-bottom:1px solid rgba(80,60,20,0.12);}
#wrapper .logo{display:flex;align-items:center;gap:7px;font-size:15px;font-weight:800;letter-spacing:-.4px;}
#wrapper.dark .logo{color:#EEEAF4;}
#wrapper.light .logo{color:#1C1917;}
#wrapper .logo-orb{width:26px;height:26px;border-radius:7px;background:var(--v);display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff;}
#wrapper .tbr{display:flex;align-items:center;gap:9px;}
#wrapper .xp-chip{display:flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;font-family:var(--mono);background:var(--vs);border:1px solid var(--vb);}
#wrapper.dark .xp-chip{color:#9B79FF;}
#wrapper.light .xp-chip{color:#5B3FD4;}
#wrapper .avi{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--v),var(--m));display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;}

/* SIDEBAR */
#wrapper .sidebar{padding:12px 9px;display:flex;flex-direction:column;gap:1px;transition:all 0.35s;}
#wrapper.dark .sidebar{background:#13131A;border-right:1px solid rgba(255,255,255,0.06);}
#wrapper.light .sidebar{background:#F0EAE0;border-right:1px solid rgba(80,60,20,0.1);}
#wrapper .nav-sec{font-size:9px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;padding:8px 8px 3px;}
#wrapper.dark .nav-sec{color:#3E3C4A;}
#wrapper.light .nav-sec{color:#A89E8E;}
#wrapper .ni{display:flex;align-items:center;gap:8px;padding:7px 9px;border-radius:8px;font-size:12px;font-weight:500;cursor:pointer;border:1px solid transparent;transition:all .15s;}
#wrapper.dark .ni{color:#8B879E;}
#wrapper.light .ni{color:#6B6256;}
#wrapper.dark .ni:hover{background:rgba(255,255,255,0.04);color:#EEEAF4;}
#wrapper.light .ni:hover{background:rgba(80,60,20,0.06);color:#1C1917;}
#wrapper .ni.on{background:var(--vs);border-color:var(--vb);}
#wrapper.dark .ni.on{color:#A78BFA;}
#wrapper.light .ni.on{color:#5B3FD4;}
#wrapper .nb{margin-left:auto;font-size:9px;font-weight:700;padding:1px 5px;border-radius:8px;background:var(--v);color:#fff;}

#wrapper .streak-box{margin-top:auto;border-radius:10px;padding:12px 10px;text-align:center;}
#wrapper.dark .streak-box{background:#18181F;border:1px solid rgba(255,255,255,0.07);}
#wrapper.light .streak-box{background:#E8E0D0;border:1px solid rgba(80,60,20,0.12);}
#wrapper .sf{font-size:22px;display:block;animation:flm 2.5s ease-in-out infinite;}
@keyframes flm{0%,100%{transform:scaleY(1) rotate(-1deg);}50%{transform:scaleY(1.1) rotate(1deg);}}
#wrapper .sn{font-family:var(--mono);font-size:22px;font-weight:700;color:var(--a);line-height:1.1;}
#wrapper .sl{font-size:10px;margin-top:2px;}
#wrapper.dark .sl{color:#3E3C4A;}
#wrapper.light .sl{color:#A89E8E;}
#wrapper .wdots{display:flex;gap:3px;justify-content:center;margin-top:8px;}
#wrapper .wd{width:8px;height:8px;border-radius:50%;}
#wrapper.dark .wd{background:rgba(255,255,255,0.1);}
#wrapper.light .wd{background:rgba(80,60,20,0.15);}
#wrapper .wd.on{background:var(--a)!important;box-shadow:0 0 4px rgba(245,166,35,.5);}
#wrapper .wd.now{background:var(--m)!important;box-shadow:0 0 4px rgba(14,207,164,.5);}

/* MAIN */
#wrapper .main{padding:16px;display:flex;flex-direction:column;gap:12px;overflow-y:auto;}

/* SECTION LABEL */
#wrapper .slb{font-size:9px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;margin-bottom:6px;}
#wrapper.dark .slb{color:#3E3C4A;}
#wrapper.light .slb{color:#A89E8E;}

/* CARDS */
#wrapper .card{border-radius:11px;padding:13px 15px;transition:all .35s;}
#wrapper.dark .card{background:#13131A;border:1px solid rgba(255,255,255,0.07);}
#wrapper.light .card{background:#FFFFFF;border:1px solid rgba(80,60,20,0.1);}

#wrapper .row{display:flex;align-items:center;gap:9px;}
#wrapper .between{justify-content:space-between;}

/* T-UTILS */
#wrapper .t1{transition:color .35s;}
#wrapper.dark .t1{color:#EEEAF4;}
#wrapper.light .t1{color:#1C1917;}
#wrapper .t2{transition:color .35s;}
#wrapper.dark .t2{color:#8B879E;}
#wrapper.light .t2{color:#6B6256;}
#wrapper .t3{transition:color .35s;}
#wrapper.dark .t3{color:#3E3C4A;}
#wrapper.light .t3{color:#A89E8E;}

/* PAGE HEADER */
#wrapper .ph{display:flex;align-items:center;justify-content:space-between;}
#wrapper .greeting{font-size:17px;font-weight:800;letter-spacing:-.4px;}
#wrapper .dtag{font-size:11px;font-weight:500;padding:3px 9px;border-radius:14px;}
#wrapper.dark .dtag{background:#18181F;border:1px solid rgba(255,255,255,0.07);}
#wrapper.light .dtag{background:#F0EAE0;border:1px solid rgba(80,60,20,0.1);}

/* EXAM CHIPS */
#wrapper .echips{display:flex;gap:6px;flex-wrap:wrap;}
#wrapper .ec{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid;cursor:pointer;transition:opacity .15s;}
#wrapper .ec:hover{opacity:.8;}
#wrapper .ec-calm{background:var(--skys);border-color:var(--skyb);color:#38BDF8;}
#wrapper.light .ec-calm{color:#0369A1;}
#wrapper .ec-warn{background:var(--as);border-color:var(--ab);color:var(--a);}
#wrapper.light .ec-warn{color:#92400E;}
#wrapper .ec-hot{background:var(--reds);border-color:var(--redb);color:var(--red);animation:hp 1.8s ease-in-out infinite;}
#wrapper.light .ec-hot{color:#991B1B;}
@keyframes hp{0%,100%{opacity:1;}50%{opacity:.6;}}
#wrapper .ec-add{background:transparent;border:1px dashed;}
#wrapper.dark .ec-add{border-color:rgba(255,255,255,.15);color:#3E3C4A;}
#wrapper.light .ec-add{border-color:rgba(80,60,20,.2);color:#A89E8E;}

/* TIMETABLE STRIP */
#wrapper .ttstrip{display:flex;gap:6px;overflow-x:auto;padding-bottom:3px;}
#wrapper .ttstrip::-webkit-scrollbar{display:none;}
#wrapper .ttb{flex-shrink:0;border-radius:8px;padding:7px 10px;border:1px solid;min-width:82px;}
#wrapper .ttb.act{border-width:1.5px;}
#wrapper .ttb-time{font-size:9px;font-family:var(--mono);}
#wrapper .ttb-name{font-size:11px;font-weight:700;margin-top:2px;line-height:1.2;}
#wrapper .ttb-type{font-size:9px;margin-top:2px;opacity:.75;}
#wrapper .pdot{width:6px;height:6px;border-radius:50%;background:var(--m);animation:pd 1.4s ease-in-out infinite;flex-shrink:0;}
@keyframes pd{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(.7);}}

/* FOCUS + TIMER GRID */
#wrapper .grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

#wrapper .focus-ring-wrap{position:relative;width:72px;height:72px;flex-shrink:0;}
#wrapper .ring-svg{transform:rotate(-90deg);}
#wrapper circle.rtrack{fill:none;stroke-width:5;}
#wrapper.dark circle.rtrack{stroke:#1F1F28;}
#wrapper.light circle.rtrack{stroke:rgba(80,60,20,.12);}
#wrapper circle.rfill{fill:none;stroke:var(--v);stroke-width:5;stroke-linecap:round;stroke-dasharray:188;stroke-dashoffset:72;animation:rA 1.2s cubic-bezier(.4,0,.2,1) both;}
@keyframes rA{from{stroke-dashoffset:188;}to{stroke-dashoffset:72;}}
#wrapper .rc{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
#wrapper .rh{font-family:var(--mono);font-size:13px;font-weight:600;line-height:1;}
#wrapper .rl{font-size:9px;margin-top:2px;}

#wrapper .timer-card{border-radius:11px;padding:13px 15px;border:1px solid rgba(14,207,164,.22);position:relative;overflow:hidden;transition:all .35s;}
#wrapper.dark .timer-card{background:#13131A;}
#wrapper.light .timer-card{background:#FFFFFF;}
#wrapper .timer-card::before{content:'';position:absolute;inset:0;background:var(--ms);pointer-events:none;}
#wrapper .live-tag{display:flex;align-items:center;gap:4px;font-size:9.5px;font-weight:700;color:var(--m);letter-spacing:.05em;}
#wrapper .stop-btn{font-size:10px;font-weight:700;padding:5px 10px;border-radius:7px;cursor:pointer;border:1px solid rgba(14,207,164,.3);background:rgba(14,207,164,.12);color:var(--m);font-family:var(--sans);transition:all .15s;}
#wrapper .stop-btn:hover{background:rgba(14,207,164,.22);}
#wrapper .tproj{font-size:10px;font-weight:600;color:var(--m);margin-top:8px;letter-spacing:.03em;}
#wrapper .tdig{font-family:var(--mono);font-size:28px;font-weight:600;letter-spacing:.02em;line-height:1.1;margin-top:3px;}
#wrapper .tbar{height:3px;border-radius:2px;margin-top:10px;overflow:hidden;}
#wrapper.dark .tbar{background:#1F1F28;}
#wrapper.light .tbar{background:rgba(80,60,20,.1);}
#wrapper .tbar-f{height:100%;background:var(--m);animation:tbG 1s ease-out forwards;width:63%;}
@keyframes tbG{to{width:63%;}}

/* DAILY PLAN CARD */
#wrapper .plan-item{display:flex;align-items:center;gap:9px;padding:7px 0;border-bottom:1px solid;}
#wrapper.dark .plan-item{border-color:rgba(255,255,255,.06);}
#wrapper.light .plan-item{border-color:rgba(80,60,20,.08);}
#wrapper .plan-item:last-child{border-bottom:none;}
#wrapper .chk{width:17px;height:17px;border-radius:5px;border:1.5px solid;flex-shrink:0;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;}
#wrapper.dark .chk{border-color:rgba(255,255,255,.2);}
#wrapper.light .chk{border-color:rgba(80,60,20,.25);}
#wrapper .chk.dn{background:var(--m);border-color:var(--m);}
#wrapper .pt{font-size:12.5px;font-weight:500;flex:1;cursor:pointer;}
#wrapper.dark .pt.dn{color:#3E3C4A;text-decoration:line-through;}
#wrapper.light .pt.dn{color:#A89E8E;text-decoration:line-through;}
#wrapper .xpbadge{display:inline-flex;align-items:center;gap:3px;font-size:9.5px;font-weight:700;padding:2px 7px;border-radius:10px;background:var(--vs);border:1px solid var(--vb);white-space:nowrap;}
#wrapper.dark .xpbadge{color:#A78BFA;}
#wrapper.light .xpbadge{color:#5B3FD4;}

/* STATS ROW */
#wrapper .stats3{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;}
#wrapper .sc{border-radius:10px;padding:11px 12px;}
#wrapper.dark .sc{background:#13131A;border:1px solid rgba(255,255,255,.07);}
#wrapper.light .sc{background:#FFFFFF;border:1px solid rgba(80,60,20,.1);}
#wrapper .sc-lbl{font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:5px;}
#wrapper.dark .sc-lbl{color:#3E3C4A;}
#wrapper.light .sc-lbl{color:#A89E8E;}
#wrapper .sc-val{font-family:var(--mono);font-size:20px;font-weight:700;line-height:1;}
#wrapper .sc-sub{font-size:10px;margin-top:3px;}
#wrapper .chip-up{display:inline-flex;align-items:center;gap:2px;font-size:9.5px;font-weight:600;padding:1px 6px;border-radius:8px;margin-top:5px;background:rgba(14,207,164,.12);color:var(--m);}
#wrapper.light .chip-up{color:#0A8A6C;}
#wrapper .chip-str{display:inline-flex;align-items:center;gap:2px;font-size:9.5px;font-weight:600;padding:1px 6px;border-radius:8px;margin-top:5px;background:var(--as);color:var(--a);}
#wrapper.light .chip-str{color:#92400E;}

/* XP + CHALLENGE */
#wrapper .xp-card{border-radius:10px;padding:12px 14px;}
#wrapper.dark .xp-card{background:#13131A;border:1px solid rgba(255,255,255,.07);}
#wrapper.light .xp-card{background:#FFFFFF;border:1px solid rgba(80,60,20,.1);}
#wrapper .lvl-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:9px;}
#wrapper .lvl-name{font-size:13px;font-weight:700;}
#wrapper .lvl-badge{font-size:9.5px;font-weight:700;padding:2px 8px;border-radius:9px;background:var(--vs);border:1px solid var(--vb);}
#wrapper.dark .lvl-badge{color:#A78BFA;}
#wrapper.light .lvl-badge{color:#5B3FD4;}
#wrapper .xptrack{height:4px;border-radius:3px;overflow:hidden;margin-bottom:5px;}
#wrapper.dark .xptrack{background:#1F1F28;}
#wrapper.light .xptrack{background:rgba(80,60,20,.12);}
#wrapper .xpfill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--v),#A78BFA);animation:xpG 1.2s .4s cubic-bezier(.4,0,.2,1) both;width:68%;}
@keyframes xpG{to{width:68%;}}
#wrapper .xp-nums{display:flex;justify-content:space-between;font-size:9.5px;font-family:var(--mono);}

#wrapper .ch-card{border-radius:10px;padding:12px 14px;position:relative;overflow:hidden;}
#wrapper.dark .ch-card{background:#13131A;border:1px solid rgba(255,255,255,.07);}
#wrapper.light .ch-card{background:#FFFFFF;border:1px solid rgba(80,60,20,.1);}
#wrapper .ch-card::before{content:'';position:absolute;top:-10px;right:-10px;width:60px;height:60px;border-radius:50%;background:var(--as);filter:blur(18px);}
#wrapper .ch-lbl{display:inline-flex;align-items:center;gap:3px;font-size:9.5px;font-weight:700;padding:2px 7px;border-radius:9px;background:var(--as);border:1px solid var(--ab);color:var(--a);margin-bottom:7px;letter-spacing:.04em;}
#wrapper.light .ch-lbl{color:#92400E;}
#wrapper .ch-title{font-size:12px;font-weight:600;line-height:1.4;margin-bottom:8px;}
#wrapper .ch-dots{display:flex;gap:4px;}
#wrapper .cd{width:11px;height:11px;border-radius:3px;}
#wrapper.dark .cd{background:#1F1F28;}
#wrapper.light .cd{background:rgba(80,60,20,.12);}
#wrapper .cd.dn{background:var(--a);}
#wrapper .ch-xp{font-size:9.5px;font-weight:600;color:var(--a);margin-left:auto;}
#wrapper.light .ch-xp{color:#92400E;}

/* HABIT ROW */
#wrapper .hrow{display:flex;align-items:center;gap:7px;padding:7px 0;border-bottom:1px solid;}
#wrapper.dark .hrow{border-color:rgba(255,255,255,.06);}
#wrapper.light .hrow{border-color:rgba(80,60,20,.08);}
#wrapper .hrow:last-child{border-bottom:none;}
#wrapper .hname{font-size:11px;font-weight:600;width:80px;flex-shrink:0;}
#wrapper .hdots2{display:flex;gap:3px;}
#wrapper .hc{width:19px;height:19px;border-radius:5px;border:1px solid;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:9px;transition:all .2s;font-weight:700;}
#wrapper.dark .hc{background:#18181F;border-color:rgba(255,255,255,.1);}
#wrapper.light .hc{background:#F5F0E8;border-color:rgba(80,60,20,.15);}
#wrapper .hc.dn{border-color:transparent;color:#fff;}
#wrapper .hstreak{font-size:10px;font-weight:700;color:var(--a);font-family:var(--mono);margin-left:auto;white-space:nowrap;}

/* QUICK START */
#wrapper .qrow{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;}
#wrapper .qb{border-radius:9px;padding:10px 8px;text-align:center;cursor:pointer;border:1px solid;background:none;font-family:var(--sans);transition:all .18s;}
#wrapper.dark .qb{background:#13131A;border-color:rgba(255,255,255,.07);}
#wrapper.light .qb{background:#FFFFFF;border-color:rgba(80,60,20,.1);}
#wrapper.dark .qb:hover{background:#1F1F28;border-color:var(--vb);}
#wrapper.light .qb:hover{background:#FAF7F2;border-color:var(--vb);}
#wrapper .qi{font-size:16px;margin-bottom:3px;}
#wrapper .ql{font-size:10.5px;font-weight:600;}
#wrapper.dark .ql{color:#8B879E;}
#wrapper.light .ql{color:#6B6256;}

/* HEATMAP */
#wrapper .hmap{border-radius:10px;padding:12px 14px;}
#wrapper.dark .hmap{background:#13131A;border:1px solid rgba(255,255,255,.07);}
#wrapper.light .hmap{background:#FFFFFF;border:1px solid rgba(80,60,20,.1);}
#wrapper .hmap-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
#wrapper .hmap-grid{display:flex;gap:3px;}
#wrapper .hmap-col{display:flex;flex-direction:column;gap:2px;}
#wrapper .hcell{width:9px;height:9px;border-radius:2px;cursor:pointer;transition:transform .1s;}
#wrapper .hcell:hover{transform:scale(1.5);}
#wrapper.dark .hcell.l0{background:#1F1F28;}
#wrapper.light .hcell.l0{background:rgba(80,60,20,.1);}
#wrapper .hcell.l1{background:rgba(124,92,252,.2);}
#wrapper .hcell.l2{background:rgba(124,92,252,.4);}
#wrapper .hcell.l3{background:rgba(124,92,252,.65);}
#wrapper .hcell.l4{background:var(--v);}
#wrapper .hcell.tdy{outline:1.5px solid var(--m);outline-offset:1px;}

/* SESSIONS */
#wrapper .scard{border-radius:10px;padding:12px 14px;}
#wrapper.dark .scard{background:#13131A;border:1px solid rgba(255,255,255,.07);}
#wrapper.light .scard{background:#FFFFFF;border:1px solid rgba(80,60,20,.1);}
#wrapper .srow{display:flex;align-items:center;gap:9px;padding:7px 0;border-bottom:1px solid;}
#wrapper.dark .srow{border-color:rgba(255,255,255,.06);}
#wrapper.light .srow{border-color:rgba(80,60,20,.08);}
#wrapper .srow:last-child{border-bottom:none;}
#wrapper .sdot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
#wrapper .sinfo{flex:1;}
#wrapper .sn{font-size:12.5px;font-weight:600;}
#wrapper .sm{font-size:10px;margin-top:1px;}
#wrapper .sdur{font-family:var(--mono);font-size:11.5px;font-weight:600;margin-left:auto;white-space:nowrap;}
#wrapper.dark .sdur{color:#8B879E;}
#wrapper.light .sdur{color:#6B6256;}

/* TOGGLE */
#wrapper .mtoggle{display:flex;align-items:center;justify-content:flex-end;gap:7px;padding:8px 12px 0;}
#wrapper .mlbl{font-size:11px;font-weight:600;}
#wrapper.dark .mlbl{color:#555;}
#wrapper.light .mlbl{color:#AAA;}
#wrapper .mtrack{width:40px;height:20px;border-radius:10px;position:relative;cursor:pointer;transition:background .3s;border:1px solid;}
#wrapper.dark .mtrack{background:#1F1F28;border-color:rgba(255,255,255,.1);}
#wrapper.light .mtrack{background:#C4B898;border-color:rgba(80,60,20,.2);}
#wrapper .mthumb{position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:var(--v);transition:transform .3s cubic-bezier(.4,0,.2,1);}
#wrapper.light .mthumb{transform:translateX(20px);background:#8B6B2E;}
#wrapper .see-all{font-size:11px;font-weight:600;cursor:pointer;color:var(--v);}
#wrapper.light .see-all{color:#5B3FD4;}

/* FADE IN */
#wrapper .fi{animation:fiA .5s ease both;}
@keyframes fiA{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
#wrapper .fd1{animation-delay:.05s;} #wrapper .fd2{animation-delay:.1s;} #wrapper .fd3{animation-delay:.15s;}
#wrapper .fd4{animation-delay:.2s;} #wrapper .fd5{animation-delay:.25s;} #wrapper .fd6{animation-delay:.3s;}
#wrapper .fd7{animation-delay:.35s;} #wrapper .fd8{animation-delay:.4s;} #wrapper .fd9{animation-delay:.45s;}
</style>
