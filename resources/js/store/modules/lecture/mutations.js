export default {
    setLecture(state, lecture) {
        state.lectures = lecture;
    },

    setLectureByMeetingUser(state, lecture) {
        state.lecture_id = lecture;
    },

    setSlots(state, slots) {
        state.slots = slots;
    },

    addFavorite(state,id) {
        let i = state.lectures.map(lecture => lecture.id).indexOf(id);
        state.lectures[i].is_favorite = true;
    },

    deleteFavorite(state,id) {
        let i = state.lectures.map(lecture => lecture.id).indexOf(id);
        state.lectures[i].is_favorite = false;
    }
}
